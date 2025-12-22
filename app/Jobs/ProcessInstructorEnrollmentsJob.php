<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\SemesterCourse;
use App\Models\Student;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BgTaskLog;
use App\Models\Faculty;
use App\Models\FacultyMember;
use App\Models\SemesterCourseInstructor;
use Illuminate\Support\Facades\Hash;
use App\Helpers\helpers;


class ProcessInstructorEnrollmentsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    protected Collection $instructorEnrollments;

    protected int $semesterCourseId;

    public function __construct(Collection $instructorEnrollments, int $semesterCourseId)
    {
        $this->instructorEnrollments = $instructorEnrollments;
        $this->semesterCourseId = $semesterCourseId;
    }

    public function handle(): void
    {
        try {

            if ($this->batch()?->cancelled()) {
                return;
            }

            $this->ensureTaskLogExists();

            $batchId = $this->batch()->id;

            $results = $this->processEnrollmentChunk();
            
            $this->updateBatchStatistics($batchId, $results);
            
            if (!empty($results['errors'])) {
                $this->saveErrorsToFile($batchId, $results['errors']);
            }

        } catch (Exception $e) {
            logErrorJob('Job processing failed', 'ProcessInstructorEnrollmentsJob', $e, [
                'batch_id' => $this->batch()?->id,
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'instructorEnrollments_count' => $this->instructorEnrollments->count(),
            ]);
            throw $e;
        }
    }

    private function ensureTaskLogExists(): void
    {
        $batchId = $this->batch()->id;
        
        $taskLog = BgTaskLog::where('task_id', $batchId)
            ->where('type', 'enrollment')
            ->first();

        if (!$taskLog) {
            BgTaskLog::create([
                'task_id' => $batchId,
                'type' => 'enrollment',
                'task_type' => 'batch',
                'status' => 'processing',
                'user_id' => $this->userId ?? null,
                'data' => json_encode([
                    'total' => $this->instructorEnrollments->count(),
                    'processed' => 0,
                    'successful' => 0,
                    'failed' => 0
                ]),
                'message' => 'Enrollment processing started',
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Process enrollment chunk and return results
     */
    private function processEnrollmentChunk(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->instructorEnrollments as $enrollment) {
            try {
                DB::transaction(function () use ($enrollment, &$successCount) {
                    $this->processSingleEnrollment($enrollment);
                    $successCount++;
                });
            } catch (Exception $e) {
                $errors[] = $this->formatError($enrollment, $e);
                logErrorJob('Instructor enrollment processing failed', 'ProcessInstructorEnrollmentsJob', $e, [
                    'batch_id' => $this->batch()?->id,
                    'job_id' => $this->job?->getJobId(),
                    'attempt' => $this->attempts(),
                    'email' => $enrollment['academic_email'] ?? 'N/A',
                    'national_id' => $enrollment['national_id'] ?? 'N/A',
                ]);
            }
        }

        return [
            'total' => $this->instructorEnrollments->count(),
            'processed' => $this->instructorEnrollments->count(),
            'successful' => $successCount,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Update batch statistics using atomic cache operations
     */
    private function updateBatchStatistics(string $batchId, array $results): void
    {
        $taskLog = BgTaskLog::where('task_id', $batchId)
            ->where('type', 'enrollment')
            ->lockForUpdate()
            ->first();

        if (!$taskLog) {
            return;
        }

        $currentData = json_decode($taskLog->data, true) ?? [
            'total' => 0,
            'processed' => 0,
            'successful' => 0,
            'failed' => 0
        ];

        // Update only the processed, successful and failed counts
        // Total should remain the same as it was set initially
        $updatedData = [
            'total' => $currentData['total'],
            'processed' => $currentData['processed'] + $results['processed'],
            'successful' => $currentData['successful'] + $results['successful'],
            'failed' => $currentData['failed'] + $results['failed'],
        ];

        $taskLog->update([
            'data' => json_encode($updatedData),
            'message' => "Processing: {$updatedData['successful']}/{$updatedData['total']} instructorEnrollments completed"
        ]);
    }

    /**
     * Format error for logging and file output
     */
    private function formatError(array $enrollment, Exception $e): array
    {
        return [
            'row' => $enrollment['row_number'] ?? 'N/A',
            'email' => $enrollment['academic_email'] ?? 'N/A',
            'national_id' => $enrollment['national_id'] ?? 'N/A',
            'error' => $e->getMessage(),
        ];
    }

    /**
     * Save errors to CSV file
     */
    private function saveErrorsToFile(string $batchId, array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        $filename = "errors/{$batchId}.csv";
        $disk = Storage::disk('public');

        try {
            $this->ensureErrorDirectory($disk, $filename);
            $csvContent = $this->formatErrorsAsCsv($errors, $disk->exists($filename));
            $disk->append($filename, $csvContent);
            
        } catch (Exception $e) {
            logErrorJob('Failed to save errors to file', 'ProcessInstructorEnrollmentsJob', $e, [
                'batch_id' => $batchId,
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'filename' => $filename,
            ]);
        }
    }

    /**
     * Ensure error directory exists
     */
    private function ensureErrorDirectory($disk, string $filename): void
    {
        $directory = dirname($disk->path($filename));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Format errors as CSV content
     */
    private function formatErrorsAsCsv(array $errors, bool $fileExists): string
    {
        $content = '';
        
        // Add header if file doesn't exist
        if (!$fileExists) {
            $content .= "Row,Email,National ID,Error\n";
        }

        foreach ($errors as $error) {
            $content .= sprintf(
                "%s,%s,%s,\"%s\"\n",
                $error['row'],
                $this->escapeCsvField($error['email']),
                $this->escapeCsvField($error['national_id']),
                $this->escapeCsvField($error['error'])
            );
        }

        return $content;
    }

    /**
     * Escape CSV field content
     */
    private function escapeCsvField(string $field): string
    {
        return str_replace(['"', ',', "\n", "\r"], ['""', '', ' ', ' '], $field);
    }

    /**
     * Process a single enrollment record
     */
    private function processSingleEnrollment(array $enrollment): void
    {
        $this->validateEnrollmentData($enrollment);

        $course = $this->findCourse($enrollment['course_code']);
        $semesterCourse = $this->findOrCreateSemesterCourse($course->id);
        $instructor = $this->findOrCreateInstructorProfile($enrollment);
        
        if ($this->instructorAssignmentExists($instructor, $semesterCourse)) {
            return;
        }

        $assignment = new SemesterCourseInstructor([
            'semester_course_id' => $semesterCourse->id,
            'faculty_member_id' => $instructor->id,
            'is_primary' => false,
            'status' => 'active'
        ]);
        $assignment->save();
    }

    /**
     * Validate required enrollment data
     */
    private function validateEnrollmentData(array $enrollment): void
    {
        $requiredFields = [
            'faculty_member_name',
            'national_id',
            'academic_email',
            'faculty',
            'course_name',
            'course_code',
        ];

        foreach ($requiredFields as $field) {
            if (empty($enrollment[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
    }

    /**
     * Find course by code
     */
    private function findCourse(string $courseCode): Course
    {
        $course = Course::where('code', $courseCode)->first();

        if (!$course) {
            throw new Exception("Course '{$courseCode}' not found");
        }

        return $course;
    }

    /**
     * Find or create semester course
     */
    private function findOrCreateSemesterCourse(int $courseId): SemesterCourse
    {
        return SemesterCourse::firstOrCreate([
            'course_id' => $courseId,
            'semester_id' => $this->semesterCourseId,
        ]);
    }

    /**
     * Find or create instructor profile
     */
    private function findOrCreateInstructorProfile(array $enrollment): FacultyMember
    {
        $email = trim($enrollment['academic_email']);
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $facultyMember = FacultyMember::where('user_id', $existingUser->id)->first();
            if ($facultyMember) {
                return $facultyMember;
            }
        }

        $user = $this->createUser($email, $enrollment['faculty_member_name']);

        $faculty = Faculty::where('name', $enrollment['faculty'])->first();

        if (!$faculty) {
            throw new Exception("Faculty '{$enrollment['faculty']}' not found");
        }

        return FacultyMember::create([
            'user_id' => $user->id,
            'national_id' => $enrollment['national_id'],
            'academic_email' => $email,
            'faculty_id' => $faculty->id,
            'position' => 'Instructor'
        ]);
    }

    /**
     * Create user account
     */
    private function createUser(string $email, string $fullName): User
    {
        $nameParts = explode(' ', $fullName, 2);
        $displayName = count($nameParts) > 1 ? $nameParts[0] . ' ' . $nameParts[1] : $nameParts[0];

        $user = User::create([
            'name' => $displayName,
            'full_name' => $fullName,
            'email' => $email,
            'password' => Hash::make(Str::random(12)),
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * Check if instructor is already assigned to the semester course
     */
    private function instructorAssignmentExists(FacultyMember $instructor, SemesterCourse $semesterCourse): bool
    {
        return SemesterCourseInstructor::where('faculty_member_id', $instructor->id)
            ->where('semester_course_id', $semesterCourse->id)
            ->exists();
    }
}
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BgTaskLog;
use App\Models\Faculty;


class ProcessEnrollmentsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    protected Collection $enrollments;

    protected int $semesterCourseId;

    protected int $userId;

    public function __construct(Collection $enrollments, int $semesterCourseId, int $userId)
    {
        $this->enrollments = $enrollments;
        $this->semesterCourseId = $semesterCourseId;
        $this->userId = $userId;
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
            logErrorJob('Job processing failed', 'ProcessEnrollmentsJob', $e, [
                'batch_id' => $this->batch()?->id,
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'enrollments_count' => $this->enrollments->count(),
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
                    'total' => $this->enrollments->count(),
                    'processed' => 0,
                    'successful' => 0,
                    'failed' => 0
                ]),
                'message' => 'Enrollment processing started',
                'created_at' => now(),
            ]);
        } else if (!$taskLog->user_id) {
            $taskLog->update(['user_id' => $this->userId]);
        }
    }

    /**
     * Process enrollment chunk and return results
     */
    private function processEnrollmentChunk(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->enrollments as $enrollment) {
            try {
                DB::transaction(function () use ($enrollment, &$successCount) {
                    $this->processSingleEnrollment($enrollment);
                    $successCount++;
                });
            } catch (Exception $e) {
                $errors[] = $this->formatError($enrollment, $e);
                logErrorJob('Enrollment processing failed', 'ProcessEnrollmentsJob', $e, [
                    'batch_id' => $this->batch()?->id,
                    'job_id' => $this->job?->getJobId(),
                    'attempt' => $this->attempts(),
                    'academic_id' => $enrollment['academic_id'] ?? 'N/A',
                ]);
            }
        }

        return [
            'total' => $this->enrollments->count(),
            'processed' => $this->enrollments->count(),
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
            'message' => "Processing: {$updatedData['successful']}/{$updatedData['total']} enrollments completed"
        ]);
    }

    /**
     * Format error for logging and file output
     */
    private function formatError(array $enrollment, Exception $e): array
    {
        return [
            'row' => $enrollment['row_number'] ?? 'N/A',
            'academic_id' => $enrollment['academic_id'] ?? 'N/A',
            'email' => $enrollment['academic_email'] ?? 'N/A',
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
            logErrorJob('Failed to save errors to file', 'ProcessEnrollmentsJob', $e, [
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
            $content .= "Row,Academic ID,Email,Error\n";
        }

        foreach ($errors as $error) {
            $content .= sprintf(
                "%s,%s,%s,\"%s\"\n",
                $error['row'],
                $this->escapeCsvField($error['academic_id']),
                $this->escapeCsvField($error['email']),
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
        
        $program = $this->findProgram($enrollment['faculty'], $enrollment['program']);
        $course = $this->findCourse($enrollment['course_code']);
        $semesterCourse = $this->findOrCreateSemesterCourse($course->id);
        $student = $this->findOrCreateStudent($enrollment, $program);

        $this->createEnrollmentIfNotExists($student->id, $course->id, $semesterCourse->id, $enrollment['academic_term']);
    }

    /**
     * Validate required enrollment data
     */
    private function validateEnrollmentData(array $enrollment): void
    {
        $requiredFields = [
            'academic_email', 'academic_id', 'academic_term',
            'course_code', 'course_name', 'credit_hours',
            'faculty', 'national_id', 'program', 'student_name'
        ];

        foreach ($requiredFields as $field) {
            if (empty($enrollment[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
    }

    /**
     * Find program by faculty and program name
     */
    private function findProgram(string $facultyName, string $programName): Program
    {
        $faculty = Faculty::where('name', $facultyName)->first();
        if (!$faculty) {
            throw new Exception("Faculty '{$facultyName}' not found");
        }

        $program = Program::whereHas('faculty', fn($query) => 
            $query->where('name', $facultyName)
        )->where('name', $programName)->first();

        if (!$program) {
            throw new Exception("Program '{$programName}' not found in faculty '{$facultyName}'");
        }

        return $program;
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
     * Find or create student profile
     */
    private function findOrCreateStudent(array $enrollment, Program $program): Student
    {
        $email = trim($enrollment['academic_email']);
        
        $existingUser = User::where('email', $email)->first();

        return $existingUser?->student ?? $this->createNewStudent($enrollment, $program);
    }

    /**
     * Create new student with user account
     */
    private function createNewStudent(array $enrollment, Program $program): Student
    {
        $email = trim($enrollment['academic_email']);
        $fullName = trim($enrollment['student_name']);
        $password = Str::random(12);

        $user = $this->createUser($email, $fullName, $password);
        $student = $this->createStudentProfile($enrollment, $user->id, $program->id);

        event(new \App\Events\UserCreated($user, $password));

        return $student;
    }

    /**
     * Create user account
     */
    private function createUser(string $email, string $fullName, string $password): User
    {
        $nameParts = explode(' ', $fullName, 2);
        $displayName = count($nameParts) > 1 ? $nameParts[0] . ' ' . $nameParts[1] : $nameParts[0];

        $user = User::create([
            'name' => $displayName,
            'full_name' => $fullName,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $user->assignRole('respondent');

        return $user;
    }

    /**
     * Create student profile
     */
    private function createStudentProfile(array $enrollment, int $userId, int $programId): Student
    {
        return Student::create([
            'name' => trim($enrollment['student_name']),
            'email' => trim($enrollment['academic_email']),
            'program_id' => $programId,
            'user_id' => $userId,
            'national_id' => $enrollment['national_id'],
            'academic_id' => $enrollment['academic_id'],
        ]);
    }

    /**
     * Create enrollment if it doesn't exist
     */
    private function createEnrollmentIfNotExists(int $studentId, int $courseId, int $semesterCourseId, string $academicTerm): void
    {
        $exists = Enrollment::where('student_id', $studentId)
            ->where('semester_course_id', $semesterCourseId)
            ->exists();

        if (!$exists) {
            Enrollment::create([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'semester_course_id' => $semesterCourseId,
                'academic_term' => $academicTerm,
                'enrollment_date' => now(),
                'status' => 'active',
            ]);
        }
    }
}
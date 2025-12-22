<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\BusinessException;
use App\Jobs\ProcessEnrollmentsJob;
use App\Jobs\ProcessInstructorEnrollmentsJob;
use App\Models\BgTaskLog;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Collection;
use Illuminate\Bus\Batch;
use Throwable;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;


class EnrollmentService extends BaseService
{
    /**
     * Get enrollment statistics for dashboard cards.
     *
     * @return array<int, array<string, mixed>>
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            $enrollmentsRepo = $this->unitOfWork->enrollments();
            $latestEnrollmentUpdate = $enrollmentsRepo->latestUpdateTime();
            
            return [
                'total_enrollments' => [
                    'value' => formatNumber($enrollmentsRepo->count()),
                    'updated' => formatDate($latestEnrollmentUpdate),
                ]
            ];
        } catch (Exception $e) {
            logError('Failed to fetch enrollment statistics', 'EnrollmentService', $e);
            throw new ServiceException('Unable to retrieve enrollment statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for enrollments.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->enrollments()->query()
                ->with([
                    'student.user', 
                    'student.program.faculty', 
                    'semesterCourse.course', 
                    'semesterCourse.semester',
                    'semesterCourse.instructors.facultyMember.user'
                ]);  

            $this->applyFilters($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('student_name', fn($enrollment) => $enrollment->student->user->full_name ?? 'N/A')
                ->addColumn('national_id', fn($enrollment) => $enrollment->student->national_id ?? 'N/A')
                ->addColumn('academic_id', fn($enrollment) => $enrollment->student->academic_id ?? 'N/A')
                ->addColumn('faculty_name', fn($enrollment) => $enrollment->student->program->faculty->name ?? 'N/A')
                ->addColumn('program_name', fn($enrollment) => $enrollment->student->program->name ?? 'N/A')
                ->addColumn('semester_name', fn($enrollment) => $enrollment->semesterCourse->semester->name ?? 'N/A')
                ->addColumn('course_name', fn($enrollment) => $enrollment->semesterCourse->course->name ?? 'N/A')
                ->addColumn('course_code', fn($enrollment) => $enrollment->semesterCourse->course->code ?? 'N/A')
                ->addColumn('instructor_name', fn($enrollment) => $enrollment->semesterCourse?->instructors?->pluck('facultyMember.user.full_name')->implode(', ') ?? 'N/A')
                ->addColumn('created_at', fn($enrollment) => formatDate($enrollment->created_at))
                ->addColumn('actions', fn($enrollment) => $this->getActionButtons($enrollment))
                ->rawColumns(['actions'])
                ->make(true);
                
        } catch (Exception $e) {
            logError('Failed to fetch dataTable data', 'EnrollmentService', $e);
            throw new ServiceException('Unable to load enrollment data due to system error', 0, $e);
        }
    }

    /**
     * Apply filters to the enrollment query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    private function applyFilters($query): void
    {
        if (request()->has('student_name') && !empty(request('student_name'))) {
            $query->whereHas('student.user', function($q) {
                $q->where('full_name', 'like', '%' . request('student_name') . '%');
            });
        }

        if (request()->has('national_id') && !empty(request('national_id'))) {
            $query->whereHas('student', function($q) {
                $q->where('national_id', 'like', '%' . request('national_id') . '%');
            });
        }

        if (request()->has('academic_id') && !empty(request('academic_id'))) {
            $query->whereHas('student', function($q) {
                $q->where('academic_id', 'like', '%' . request('academic_id') . '%');
            });
        }

        if (request()->has('course_id') && !empty(request('course_id'))) {
            $query->whereHas('semesterCourse', function($q) {
                $q->where('course_id', request('course_id'));
            });
        }

        if (request()->has('faculty_id') && !empty(request('faculty_id'))) {
            $query->whereHas('student.program.faculty', function($q) {
                $q->where('id', request('faculty_id'));
            });
        }

        if (request()->has('program_id') && !empty(request('program_id'))) {
            $query->whereHas('student.program', function($q) {
                $q->where('id', request('program_id'));
            });
        }

        if (request()->has('start_date') && !empty(request('start_date'))) {
            $query->where('created_at', '>=', request('start_date'));
        }

        if (request()->has('end_date') && !empty(request('end_date'))) {
            $query->where('created_at', '<=', request('end_date'));
        }
    }

    /**
     * Generate HTML action buttons for enrollment.
     *
     * @param Enrollment $enrollment
     * @return string
     */
    private function getActionButtons(Enrollment $enrollment): string 
    {
        return '<div class="btn-group">
            <a href="javascript:void(0)" 
               class="btn btn-outline-danger rounded ms-2 delete-enrollment" 
               data-id="' . $enrollment->id . '"
               title="حذف">
                <i class="fa fa-trash"></i>
            </a>
        </div>';
    }

    /**
     * Get dashboard data for enrollment management.
     *
     * @return array
     * @throws ServiceException
     */
    public function getDashboardData(): array
    {
        try {
            return [
                'programs' => $this->unitOfWork->programs()->all(),
                'faculties' => $this->unitOfWork->faculties()->all(),
                'courses' => $this->unitOfWork->courses()->all(),
                'semesters' => $this->unitOfWork->semesters()->all()
            ];
        } catch (Exception $e) {
            logError('Error fetching dashboard data', 'EnrollmentService', $e);
            throw new ServiceException('Failed to fetch dashboard data', 0, $e);
        }
    }

    /**
     * Create a single enrollment record.
     *
     * @param int $studentId
     * @param int $semesterId
     * @param int $courseId
     * @return Enrollment
     * @throws BusinessValidationException|ServiceException
     */
    public function createSingleEnrollment(int $studentId, int $semesterId, int $courseId): Enrollment
    {
        try {
            $semesterCourse = $this->unitOfWork->semesterCourses()->findByCourseAndSemester($courseId, $semesterId);

            if (!$semesterCourse) {
                throw new BusinessValidationException(__('Course not available in this semester'), 400);
            }

            $existingEnrollment = $this->unitOfWork->enrollments()->findByStudentAndSemesterCourse($studentId, $semesterCourse->id);

            if ($existingEnrollment) {
                throw new BusinessValidationException(__('Student is already enrolled in this course'), 400);
            }

            return $this->unitOfWork->enrollments()->createSingleEnrollment([
                'student_id' => $studentId,
                'semester_course_id' => $semesterCourse->id,
            ]);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to create enrollment', 'EnrollmentService', $e);
            throw new ServiceException('Unable to create enrollment due to system error', 0, $e);
        }
    }

    /**
     * Delete an enrollment and its associated questionnaire responses.
     *
     * @param Enrollment $enrollment
     * @return bool
     * @throws ServiceException
     */
    public function deleteEnrollment(Enrollment $enrollment): bool
    {
        try {
            $this->unitOfWork->beginTransaction();
            
            // Delete associated questionnaire responses
            $this->unitOfWork->responses()->deleteResponsesByStudentAndSemesterCourse(
                $enrollment->student_id,
                $enrollment->semester_course_id
            );
            
            // Delete the enrollment
            $this->unitOfWork->enrollments()->delete($enrollment->id);
            
            $this->unitOfWork->commit();
            return true;
        } catch (Exception $e) {
            $this->unitOfWork->rollback();
            logError('Failed to delete enrollment and responses', 'EnrollmentService', $e);
            throw new ServiceException('Unable to delete enrollment due to system error', 0, $e);
        }
    }

    /**
     * Process enrollments in batches with proper logging and error handling
     *
     * @param Collection $enrollments
     * @param int $semesterCourseId
     * @param int|null $userId
     * @return string
     * @throws ServiceException
     */
    public function processEnrollments(Collection $enrollments, int $semesterCourseId, ?int $userId = null): string
    {
        try {
            $this->validateInput($enrollments);
            
            $chunks = $enrollments->chunk(500);
            $jobs = $this->createBatchJobs($chunks, $semesterCourseId, $userId);
            $batch = $this->dispatchBatch($jobs);
            return $batch->id;
        } catch (Exception $e) {
            logError('Failed to process enrollments', 'EnrollmentService', $e);
            throw new ServiceException('Unable to process enrollments due to system error', 0, $e);
        }
    }

    /**
     * Process instructor enrollments in batches
     *
     * @param Collection $instructorEnrollments
     * @param int $semesterCourseId
     * @return string
     * @throws ServiceException
     */
    public function processInstructorEnrollments(Collection $instructorEnrollments, int $semesterCourseId): string
    {
        try {
            $this->validateInput($instructorEnrollments);
            
            $chunks = $instructorEnrollments->chunk(500);
            $jobs = $this->createInstructorBatchJobs($chunks, $semesterCourseId);
            $batch = $this->dispatchBatch($jobs);

            return $batch->id;
        } catch (Exception $e) {
            logError('Failed to process instructor enrollments', 'EnrollmentService', $e);
            throw new ServiceException('Unable to process instructor enrollments due to system error', 0, $e);
        }
    }

    /**
     * Import student enrollments from file
     * 
     * @param Collection $enrollments
     * @param int $semesterId
     * @param int $userId
     * @return array
     * @throws BusinessValidationException|ServiceException
     */
    public function importStudentEnrollments(Collection $enrollments, int $semesterId, int $userId): array
    {
        try {
            if ($enrollments->isEmpty()) {
                throw new BusinessValidationException(__('File is empty or contains no valid data'), 400);
            }

            $taskId = $this->processEnrollments($enrollments, $semesterId, $userId);

            return [
                'taskId' => $taskId,
                'enrollments' => $enrollments,
                'count' => $enrollments->count(),
            ];
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to import enrollments', 'EnrollmentService', $e);
            throw new ServiceException(__('An error occurred while uploading the file. Please try again.'), 0, $e);
        }
    }

    /**
     * Import instructor enrollments from file
     * 
     * @param Collection $enrollments
     * @param int $semesterId
     * @param int $userId
     * @return array
     * @throws BusinessValidationException|ServiceException
     */
    public function importInstructorEnrollments(Collection $enrollments, int $semesterId, int $userId): array
    {
        try {
            if ($enrollments->isEmpty()) {
                throw new BusinessValidationException(__('File is empty or contains no valid data'), 400);
            }

            $taskId = $this->processInstructorEnrollments($enrollments, $semesterId, $userId);

            return [
                'taskId' => $taskId,
                'enrollments' => $enrollments,
                'count' => $enrollments->count(),
            ];
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to import instructor enrollments', 'EnrollmentService', $e);
            throw new ServiceException(__('An error occurred while uploading the file. Please try again.'), 0, $e);
        }
    }

    /**
     * Get progress of an enrollment import task
     * 
     * @param string $taskId
     * @return array
     * @throws BusinessValidationException|ServiceException
     */
    public function getTaskProgress(string $taskId): array
    {
        try {
            $taskLog = BgTaskLog::where('task_id', $taskId)->first();
            
            if (!$taskLog) {
                throw new BusinessValidationException('Task not found', 404);
            }

            switch ($taskLog->status) {
                case 'pending':
                    return [
                        'status' => 'pending',
                        'progress' => 0,
                        'message' => 'Task is waiting to be processed'
                    ];

                case 'failed':
                    throw new BusinessValidationException('Task failed: ' . $taskLog->message, 500);

                case 'completed':
                    return [
                        'status' => $taskLog->status,
                        'progress' => 100,
                        'message' => $taskLog->message,
                        'data' => json_decode($taskLog->data, true),
                        'file' => Storage::url($taskLog->file),
                        'completed_at' => $taskLog->completed_at
                    ];

                case 'completed_with_errors':
                    return [
                        'status' => $taskLog->status,
                        'progress' => 100,
                        'message' => $taskLog->message,
                        'data' => json_decode($taskLog->data, true),
                        'file' => $taskLog->file ? asset('storage/' . $taskLog->file) : null,
                        'completed_at' => $taskLog->completed_at
                    ];

                case 'processing':
                    return $this->getBatchProgressData($taskId, $taskLog);

                default:
                    throw new BusinessValidationException('Unknown task status', 500);
            }

        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Error fetching task progress', 'EnrollmentService', $e);
            throw new ServiceException('Error fetching task progress', 0, $e);
        }
    }

    /**
     * Get students for Select2 dropdown
     * 
     * @param string|null $search
     * @return array
     * @throws ServiceException
     */
    public function getStudentsForSelect2(?string $search = null): array
    {
        try {
            $query = $this->unitOfWork->students()->query()
                ->with(['user', 'program.faculty'])
                ->when($search, function ($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->whereHas('user', function ($q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhere('academic_id', 'like', "%{$search}%");
                    });
                })
                ->limit(10);

            $students = $query->get()->map(function ($student) {
                return [
                    'id' => $student->id,
                    'text' => $student->user->full_name . ' (' . $student->academic_id . ')',
                    'student_id' => $student->academic_id,
                    'name' => $student->user->full_name,
                    'faculty_name' => $student->program->faculty->name,
                    'program_name' => $student->program->name
                ];
            });

            return [
                'results' => $students,
                'pagination' => [
                    'more' => false
                ]
            ];
        } catch (Exception $e) {
            logError('Error fetching students for Select2', 'EnrollmentService', $e);
            throw new ServiceException('Error fetching students', 0, $e);
        }
    }

    /**
     * Get courses for Select2 dropdown
     * 
     * @param string|null $search
     * @return array
     * @throws ServiceException
     */
    public function getCoursesForSelect2(?string $search = null): array
    {
        try {
            $query = $this->unitOfWork->courses()->query()
                ->with('faculty')
                ->when($search, function ($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
                })
                ->limit(10);

            $courses = $query->get()->map(function ($course) {
                return [
                    'id' => $course->id,
                    'text' => $course->name . ' (' . $course->code . ')',
                    'code' => $course->code,
                    'name' => $course->name,
                    'faculty' => $course->faculty->name
                ];
            });

            return [
                'results' => $courses,
                'pagination' => [
                    'more' => false
                ]
            ];
        } catch (Exception $e) {
            logError('Error fetching courses for Select2', 'EnrollmentService', $e);
            throw new ServiceException('Error fetching courses', 0, $e);
        }
    }

    /**
     * Validate input parameters
     *
     * @param Collection $enrollments
     * @throws InvalidArgumentException
     */
    private function validateInput(Collection $enrollments): void
    {
        if ($enrollments->isEmpty()) {
            throw new InvalidArgumentException('No enrollments provided for processing');
        }
    }

    /**
     * Create batch jobs for processing
     *
     * @param Collection $chunks
     * @param int $semesterCourseId
     * @param int|null $userId
     * @return array
     */
    private function createBatchJobs(Collection $chunks, int $semesterCourseId, ?int $userId = null): array
    {
        return $chunks->map(function ($chunk) use ($semesterCourseId, $userId) {
            return new ProcessEnrollmentsJob($chunk, $semesterCourseId, $userId);
        })->toArray();
    }

    /**
     * Create instructor batch jobs for processing
     *
     * @param Collection $chunks
     * @param int $semesterCourseId
     * @return array
     */
    private function createInstructorBatchJobs(Collection $chunks, int $semesterCourseId): array
    {
        return $chunks->map(function ($chunk) use ($semesterCourseId) {
            return new ProcessInstructorEnrollmentsJob($chunk, $semesterCourseId);
        })->toArray();
    }

    /**
     * Dispatch batch with callbacks
     *
     * @param array $jobs
     * @return Batch
     */
    private function dispatchBatch(array $jobs): Batch
    {
        return Bus::batch($jobs)
            ->then([$this, 'handleBatchSuccess'])
            ->catch([$this, 'handleBatchFailure'])
            ->dispatch();
    }

    /**
     * Handle successful batch completion
     *
     * @param Batch $batch
     * @return void
     */
    public function handleBatchSuccess(Batch $batch): void
    {
        $taskLog = $this->findTaskLog($batch->id);
        
        if (!$taskLog) {
            logError("Task log not found for batch {$batch->id}", 'EnrollmentService');
            return;
        }

        // Get final statistics from Redis cache set by jobs
        $stats = $this->getBatchStatistics($batch->id);
        
        $status = $stats['failed'] > 0 ? 'completed_with_errors' : 'completed';
        $errorFile = $stats['failed'] > 0 ? "errors/{$batch->id}.csv" : null;

        $this->updateTaskLogFinal($batch->id, [
            'status' => $status,
            'data' => $stats,
            'file' => $errorFile,
            'message' => "Completed: {$stats['successful']}/{$stats['total']} enrollments processed successfully.",
        ]);

        $this->notifyUser($taskLog);
    }

    /**
     * Handle batch failure
     *
     * @param Batch $batch
     * @param Throwable $exception
     * @return void
     */
    public function handleBatchFailure(Batch $batch, Throwable $exception): void
    {
        $this->updateTaskLogFinal($batch->id, [
            'status' => 'failed',
            'data' => ['error' => $exception->getMessage()],
            'message' => 'Batch processing failed: ' . $exception->getMessage(),
        ]);

        logError("Batch {$batch->id} failed", 'EnrollmentService', $exception);
        
        $taskLog = $this->findTaskLog($batch->id);
        if ($taskLog) {
            $this->notifyUser($taskLog);
        }
    }

    /**
     * Get batch statistics from cache
     *
     * @param string $batchId
     * @return array
     */
    private function getBatchStatistics(string $batchId): array
    {
        $taskLog = BgTaskLog::where('task_id', $batchId)
            ->where('type', 'enrollment')
            ->first();

        if (!$taskLog) {
            return [
                'total' => 0,
                'processed' => 0,
                'successful' => 0,
                'failed' => 0
            ];
        }

        return json_decode($taskLog->data, true) ?? [];
    }

    /**
     * Find task log by batch ID
     *
     * @param string $batchId
     * @return BgTaskLog|null
     */
    private function findTaskLog(string $batchId): ?BgTaskLog
    {
        return $this->unitOfWork->bgTaskLogs()->findByTaskIdAndType($batchId, 'enrollment');
    }

    /**
     * Update task log with final results
     *
     * @param string $batchId
     * @param array $updates
     * @return void
     */
    private function updateTaskLogFinal(string $batchId, array $updates): void
    {
        $taskLog = $this->findTaskLog($batchId);
        
        if (!$taskLog) {
            logError("Cannot update task log - not found for batch {$batchId}", 'EnrollmentService');
            return;
        }

        $taskLog->update([
            'status' => $updates['status'],
            'data' => json_encode($updates['data']),
            'file' => $updates['file'] ?? null,
            'message' => $updates['message'],
            'completed_at' => now(),
        ]);
    }

    /**
     * Send notification to user
     *
     * @param BgTaskLog $taskLog
     * @return void
     */
    private function notifyUser(BgTaskLog $taskLog): void
    {
        if (!$taskLog->user_id) {
            return;
        }

        $user = $this->unitOfWork->users()->find($taskLog->user_id);
        
        if ($user) {
            $user->notify(new \App\Notifications\EnrollmentTaskCompleted($taskLog));
        }
    }

    /**
     * Get batch progress data for processing tasks
     * 
     * @param string $taskId
     * @param BgTaskLog $taskLog
     * @return array
     */
    private function getBatchProgressData(string $taskId, BgTaskLog $taskLog): array
    {
        try {
            $batch = Bus::findBatch($taskId);
            
            if (!$batch) {
                return [
                    'status' => 'processing',
                    'progress' => 0,
                    'message' => 'Batch not found but task is being processed',
                    'total_jobs' => 0,
                    'processed_jobs' => 0,
                    'failed_jobs' => 0
                ];
            }

            $progressPercentage = $batch->progress();
            $stats = cache()->get("batch_stats:{$taskId}", []);

            return [
                'status' => 'processing',
                'progress' => $progressPercentage,
                'message' => "Processing: {$batch->processedJobs()}/{$batch->totalJobs} jobs completed",
                'total_jobs' => $batch->totalJobs,
                'processed_jobs' => $batch->processedJobs(),
                'pending_jobs' => $batch->pendingJobs,
                'failed_jobs' => $batch->failedJobs,
                'cancelled_at' => $batch->cancelledAt,
                'created_at' => $batch->createdAt,
                'finished_at' => $batch->finishedAt,
                'stats' => $stats
            ];

        } catch (Exception $e) {
            logError('Error getting batch progress', 'EnrollmentService', $e);
            
            return [
                'status' => 'processing',
                'progress' => null,
                'message' => 'Task is being processed (progress unavailable)',
                'error' => 'Could not fetch detailed progress'
            ];
        }
    }
}
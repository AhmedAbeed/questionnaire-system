<?php

namespace App\Services;

use App\Exceptions\{BusinessValidationException,ServiceException};
use App\Models\{Course,Semester};
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Yajra\DataTables\Facades\DataTables;

class CourseService extends BaseService
{
    /**
     * Get course statistics for dashboard cards.
     *
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            $coursesRepo = $this->unitOfWork->courses();
            
            return [
                'total_courses' => [
                    'value' => formatNumber($coursesRepo->count()),
                    'updated' => formatDate($coursesRepo->latestUpdateTime()),
                ],
            ];
        } catch (Exception $e) {
            logError('Failed to fetch course statistics', 'CourseService', $e);
            throw new ServiceException('Unable to retrieve course statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for courses.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->courses()->query()
                ->with(['faculty', 'semesterCourses.enrollments']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code', fn($course) => $course->code)
                ->addColumn('name', fn($course) => $course->name)
                ->addColumn('faculty', fn($course) => $course->faculty->name ?? 'N/A')
                ->addColumn('students_count', fn($course) => $this->countStudents($course))
                ->addColumn('credit_hours', fn($course) => $course->credit_hours)
                ->addColumn('created_at', fn($course) => formatDate($course->created_at))
                ->addColumn('actions', fn($course) => $this->getActionButtons($course))
                ->rawColumns(['actions'])
                ->make(true);
                
        } catch (Exception $e) {
            logError('Failed to fetch dataTable data', 'CourseService', $e);
            throw new ServiceException('Unable to load course data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for course.
     *
     * @param Course $course
     */
    private function getActionButtons($course): string 
    {
        return '<div class="btn-group">
            <a href="' . route('academic.courses.show', $course->id) . '" 
               class="btn btn-outline-info rounded ms-2" 
               title="عرض">
                <i class="fa fa-eye"></i>
            </a>
            <a href="javascript:void(0)" 
               class="btn btn-outline-danger rounded ms-2 delete-course" 
               data-id="' . $course->id . '"
               title="حذف">
                <i class="fa fa-trash"></i>
            </a>
        </div>';
    }

    /**
     * Delete a course by ID.
     *
     * @param int $id
     * @throws ServiceException|BusinessValidationException
     */
    public function delete(int $id): void
    {
        try {

            $course = $this->unitOfWork->courses()->find($id);
            
            if (!$course) {
                logWarning('Course not found', 'CourseService', [
                    'course_id' => $id
                ]);
                throw new BusinessValidationException("Course not found with id: $id");
            }
            
            $this->unitOfWork->beginTransaction();
            
            // Clean up related data
            $this->cleanupQuestionnaireTargets($id);
            
            // Delete the course
            $this->unitOfWork->courses()->delete($id);
            
            $this->unitOfWork->commit();
            
        } catch (BusinessValidationException $e) {
            $this->unitOfWork->rollback();
            throw $e;
            
        } catch (Exception $e) {
            $this->unitOfWork->rollback();
            
            logError('Failed to delete course', 'CourseService', $e, [
                'course_id' => $id
            ]);
            
            throw new ServiceException('Unable to delete course due to system error', 0, $e);
        }
    }

    /**
     * Get all courses.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ServiceException
     */
    public function all()
    {
        try {
            return $this->unitOfWork->courses()->all();
        } catch (Exception $e) {
            logError('Failed to retrieve all courses', 'CourseService', $e);
            throw new ServiceException('Unable to retrieve courses due to system error', 0, $e);
        }
    }

    /**
     * Find a course by its ID
     *
     * @param int $id The course ID
     * @return Course|null
     * @throws ServiceException When an unexpected error occurs
     */
    public function find(int $id): ?Course
    {
        try {
            $course = $this->unitOfWork->courses()->find($id);
            
            if (!$course) {
                logWarning('Course not found', 'CourseService', [
                    'course_id' => $id
                ]);
                
                throw new BusinessValidationException("Course not found with id: $id");
            }
            
            return $course;
            
        } catch (BusinessValidationException $e) {
            throw $e;
            
        } catch (Exception $e) {
            logError('Unexpected error finding course', 'CourseService', $e, [
                'course_id' => $id
            ]);
            
            throw new ServiceException('Unable to retrieve course due to system error', 0, $e);
        }
    }

    /**
     * Get the base query builder for courses
     * 
     * @return Builder<Course>
     * @throws ServiceException When system error occurs
     */
    public function query(): Builder
    {
        try {
            return $this->unitOfWork->courses()->query();
            
        } catch (Exception $e) {
            logError('Failed to create course query', 'CourseService', $e);
            throw new ServiceException('Unable to create course query due to system error', 0, $e);
        }
    }

    /**
     * Get course-specific questionnaire data for DataTable
     *
     * @param Course $course The course to get questionnaire data for
     * @return mixed
     * @throws ServiceException When an error occurs during data retrieval
     */
    public function questionnaireDataTable(Course $course): mixed
    {
        try {
            $deployedQuestionnairesQuery = $this->unitOfWork->deployedQuestionnaires()
                ->query()
                ->whereHas('targets', function($query) use ($course) {
                    $query->whereHas('semesterCourse', function($q) use ($course) {
                        $q->where('course_id', $course->id);
                    });
                })
                ->with(['targets.semesterCourse.semester', 'targets.semesterCourse.instructors.facultyMember.user']);

            return DataTables::of($deployedQuestionnairesQuery)
                ->addIndexColumn()
                ->addColumn('name', fn($questionnaire) => $questionnaire->name ?? 'N/A')
                ->addColumn('status', fn($questionnaire) => $questionnaire->status ?? 'N/A')
                ->addColumn('students_count', fn($questionnaire) => $this->countStudentsPerSemester($questionnaire->targets->first()?->semesterCourse?->course, $questionnaire->targets->first()?->semesterCourse?->semester))
                ->addColumn('completion_rate', fn($questionnaire) => $this->completionRate($questionnaire->id))
                ->addColumn('semester', fn($questionnaire) => $this->semesterName($questionnaire))
                ->addColumn('instructor', fn($questionnaire) => $this->instructorNames($questionnaire))
                ->addColumn('created_at', fn($questionnaire) => formatDate($questionnaire->created_at))
                ->addColumn('actions', fn($questionnaire) => $this->renderQuestionnaireActions($questionnaire))
                ->rawColumns(['actions'])
                ->make(true);
                
        } catch (Exception $e) {
            logError('Table data error', 'CourseService', $e, [
                'course_id' => $course->id
            ]);
            
            throw new ServiceException('Unable to load questionnaire data due to system error', 0, $e);
        }
    }

    /**
     * Calculate students count for a course
     *
     * @param Course $course
     * @return int
     */
    public function countStudents(Course $course): int
    {
        return $course->semesterCourses
            ->flatMap(fn($sc) => $sc->enrollments)
            ->pluck('student_id')
            ->unique()
            ->count();
    }

    /**
     * Count students per semester for a course
     *
     * @param Course $course
     * @param Semester|null $semester
     * @return int
     */
    public function countStudentsPerSemester(Course $course, ?Semester $semester): int
    {
        if (!$semester) {
            return 0;
        }
        
        return $course->semesterCourses
            ->where('semester_id', $semester->id)
            ->flatMap(fn($sc) => $sc->enrollments)
            ->pluck('student_id')
            ->unique()
            ->count();
    }

    /**
     * Get completion rate for a questionnaire
     *
     * @param int $questionnaireId
     * @return string
     */
    private function completionRate(int $questionnaireId): string
    {
        try {
            $rate = $this->unitOfWork->deployedQuestionnaires()->completionRate($questionnaireId);
            return formatPercentage($rate);
            
        } catch (Exception $e) {
            logError('Failed to get completion rate', 'CourseService', $e, [
                'questionnaire_id' => $questionnaireId
            ]);
            
            return 'N/A';
        }
    }

    /**
     * Get semester name for a questionnaire
     *
     * @param mixed $questionnaire
     * @return string
     */
    private function semesterName($questionnaire): string
    {
        return $questionnaire->targets->first()?->semesterCourse?->semester?->name ?? 'N/A';
    }

    /**
     * Get instructor names for a questionnaire
     *
     * @param mixed $questionnaire
     * @return string
     */
    private function instructorNames($questionnaire): string
    {
        return $questionnaire->targets->first()?->semesterCourse?->instructors?->pluck('facultyMember.user.full_name')->implode(', ') ?? 'N/A';
    }

    /**
     * Get action buttons HTML for a questionnaire
     *
     * @param mixed $questionnaire The questionnaire to generate buttons for
     * @return string HTML string containing action buttons
     */
    private function renderQuestionnaireActions($questionnaire): string
    {
        return '<div class="btn-group" role="group">' .
            '<a href="' . route('response.report', $questionnaire->id) . 
            '" class="btn btn-sm btn-info" title="عرض التقرير">' .
            '<i class="fa fa-eye"></i></a></div>';
    }

    

    /**
     * Manually delete targets to trigger model boot and cascade questionnaire deletion
     *
     * @param int $courseId The ID of the course
     * @return void
     */
    private function cleanupQuestionnaireTargets(int $courseId): void
    {
        try {
            $targets = $this->unitOfWork
                ->deployedQuestionnaireTargets()
                ->findByCourse($courseId);

            if ($targets->isNotEmpty()) {
                $targets->each(fn($target) => $target->delete());
            }
            
        } catch (Exception $e) {
            logError('Failed to cleanup questionnaire targets', 'CourseService', $e, [
                'course_id' => $courseId
            ]);
            throw $e;
        }
    }
}
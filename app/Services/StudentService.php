<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use App\Models\Student;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class StudentService extends BaseService
{
    /**
     * Get student statistics for dashboard cards.
     *
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            $studentRepo = $this->unitOfWork->students();
            
            return [
                'total_students' => [
                    'value' => formatNumber($studentRepo->count(), 0),
                    'updated' => formatDate($studentRepo->latestUpdateTime()),
                ],
            ];
        } catch (Exception $e) {
            logError('Failed to fetch student statistics', 'StudentService', $e);
            throw new ServiceException('Unable to retrieve student statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for students.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->students()->query()
                ->with(['program.faculty','enrollments','user']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name', fn($student) => $student->user->name)
                ->addColumn('national_id', fn($student) => $student->national_id)
                ->addColumn('academic_id', fn($student) => $student->academic_id)
                ->addColumn('enrollments_count', fn($student) => $student->enrollments->count())
                ->addColumn('created_at', fn($student) => formatDate($student->created_at))
                ->addColumn('actions', fn($student) => $this->getActionButtons($student))
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to fetch dataTable data', 'StudentService', $e);
            throw new ServiceException('Unable to load student data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for student.
     *
     * @param Student $student
     */
    private function getActionButtons($student): string 
    {
        return '<div class="btn-group">
            <a href="javascript:void(0)" 
               class="btn btn-outline-danger rounded ms-2 delete-student" 
               data-id="' . $student->id . '"
               title="حذف">
                <i class="fa fa-trash"></i>
            </a>
        </div>';
    }

    /**
     * Find a student by ID
     *
     * @param int $id
     * @return Student
     * @throws BusinessException|Exception
     */
    public function find($id)
    {
        try {
            $student = $this->unitOfWork->students()->find($id);
            if (!$student) {
                throw new BusinessValidationException('Student not found', 404);
            }
            return $student;
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Student retrieval error', 'StudentService', $e);
            throw new ServiceException('Unable to retrieve student due to system error', 0, $e);
        }
    }

    /**
     * Delete a student by ID.
     *
     * @param int $id
     * @throws ServiceException|BusinessValidationException
     */
    public function delete(int $id): void
    {
        try {
            $student = $this->unitOfWork->students()->find($id);
            if (!$student) {
                throw new BusinessValidationException('Student not found', 404);
            }
            $this->unitOfWork->students()->delete($id);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to delete student', 'StudentService', $e);
            throw new ServiceException('Unable to delete student due to system error', 0, $e);
        }
    }

    /**
     * Get all students.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ServiceException
     */
    public function all()
    {
        try {
            return $this->unitOfWork->students()->all();
        } catch (Exception $e) {
            logError('Failed to retrieve all students', 'StudentService', $e);
            throw new ServiceException('Unable to retrieve students due to system error', 0, $e);
        }
    }

    /**
     * Get the base query builder for students
     *
     * @return mixed
     * @throws ServiceException When system error occurs
     */
    public function query()
    {
        try {
            return $this->unitOfWork->students()->query();
        } catch (\Exception $e) {
            logError('Failed to create student query', 'StudentService', $e, ['user_id' => auth()->id()]);
            throw new ServiceException('Unable to create student query due to system error', 0, $e);
        }
    }

    /**
     * Prepare student table data for DataTable
     *
     * @param Student $student
     * @return array
     * @throws Exception
     */
    public function getStudentSpecificDataTable($student)
    {
        try {
            $deployedQuestionnairesQuery = $this->unitOfWork->deployedQuestionnaires()
                ->query()
                ->whereHas('targets', function($query) use ($student) {
                    $query->whereHas('semesterStudent', function($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                })
                ->with(['targets.semesterStudent.semester', 'targets.semesterStudent.instructors.facultyMember.user']);

            return DataTables::of($deployedQuestionnairesQuery)
                ->addIndexColumn()
                ->addColumn('name', fn($questionnaire) => $questionnaire->name ?? 'N/A')
                ->addColumn('status', fn($questionnaire) => $questionnaire->status ?? 'N/A')
                ->addColumn('completion_rate', fn($questionnaire) => 
                    $this->unitOfWork->deployedQuestionnaires()->completionRate($questionnaire->id) . '%' ?? 'N/A')
                ->addColumn('semester', fn($questionnaire) => 
                    $questionnaire->targets->first()?->semesterStudent?->semester?->name ?? 'N/A')
                ->addColumn('instructor', fn($questionnaire) => 
                    $questionnaire->targets->first()?->semesterStudent?->instructors?->pluck('facultyMember.user.full_name')->implode(', ') ?? 'N/A')
                ->addColumn('created_at', fn($questionnaire) => $questionnaire->created_at
                    ? $questionnaire->created_at->locale('ar')->translatedFormat('d F Y h:i:s A')
                    : 'N/A')
                ->addColumn('actions', fn($questionnaire) => 
                    '<div class="btn-group" role="group">' .
                    '<a href="' . route('response.report', $questionnaire->id) . 
                    '" class="btn btn-sm btn-info" title="عرض التقرير">' .
                    '<i class="fa fa-eye"></i></a></div>')
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Table data error', 'StudentService', $e, ['user_id' => auth()->id()]);
            throw new ServiceException('Unable to load student-specific table data due to system error', 0, $e);
        }
    }
}
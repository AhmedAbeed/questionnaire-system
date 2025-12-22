<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\FacultyMember;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class FacultyMemberService extends BaseService
{

    public function find($id){
        try {
            $facultyMember = $this->unitOfWork->facultyMembers()->find($id);
            if (!$facultyMember) {
                throw new BusinessValidationException('Faculty Member not found', 404);
            }
            return $facultyMember;
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Faculty Member retrieval error', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to retrieve faculty member due to system error', 0, $e);
        }
    }
    /**
     * Get faculty member statistics for dashboard cards.
     *
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            $facultyMemberRepo = $this->unitOfWork->facultyMembers();
            return [
                'total_faculty_members' => [
                    'value' => formatNumber($facultyMemberRepo->count()),
                    'updated' => formatDate($facultyMemberRepo->latestUpdateTime()),
                ],
            ];
        } catch (Exception $e) {
            logError('Stats retrieval error', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to retrieve faculty member statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for faculty members.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->facultyMembers()->query()->with(['faculty', 'user', 'semesterCourses']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name', fn($facultyMember) => $facultyMember->user->full_name)
                ->addColumn('national_id', fn($facultyMember) => $facultyMember->national_id)
                ->addColumn('faculty', fn($facultyMember) => $facultyMember->faculty->name)
                ->addColumn('total_courses', fn($facultyMember) => $facultyMember->semesterCourses->count())
                ->addColumn('actions', fn($facultyMember) => $this->getActionButtons($facultyMember))
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to load faculty member data', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to load faculty member data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for faculty member.
     *
     * @param $facultyMember
     * @return string
     */
    private function getActionButtons($facultyMember): string
    {
        return '<div class="btn-group">'
            . '<a href="' . route('academic.faculty-member.show', $facultyMember->id) . '" '
            . 'class="btn btn-outline-info rounded ms-2" title="عرض">'
            . '<i class="fa fa-eye"></i></a>'
            . '<a href="javascript:void(0)" class="btn btn-outline-danger rounded ms-2 delete-faculty-member" '
            . 'data-id="' . $facultyMember->id . '" title="حذف">'
            . '<i class="fa fa-trash"></i></a>'
            . '</div>';
    }

    /**
     * Delete a faculty member by ID.
     *
     * @param int $id
     * @throws ServiceException|BusinessValidationException
     */
    public function delete(int $id): void
    {
        try {
            $facultyMember = $this->unitOfWork->facultyMembers()->find($id);
            if (!$facultyMember) {
                throw new BusinessValidationException('Faculty Member not found', 404);
            }
            $this->unitOfWork->facultyMembers()->delete($id);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to delete faculty member', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to delete faculty member due to system error', 0, $e);
        }
    }

    /**
     * Get all faculty members.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ServiceException
     */
    public function all()
    {
        try {
            return $this->unitOfWork->facultyMembers()->all();
        } catch (Exception $e) {
            logError('Failed to retrieve all faculty members', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to retrieve faculty members due to system error', 0, $e);
        }
    }


    /**
     * Prepare faculty members table data for DataTable
     *
     * @param FacultyMember $facultyMember
     * @return array
     * @throws Exception
     */
    public function getFacultyMemberSpecificDataTable($facultyMember)
    {
        try {
            $deployedQuestionnairesQuery = $this->unitOfWork->deployedQuestionnaires()
                ->query()
                ->whereHas('targets', function($query) use ($facultyMember) {
                    $query->whereHas('semesterCourse', function($q) use ($facultyMember) {
                        $q->whereHas('instructors', function($instructorQuery) use ($facultyMember) {
                            $instructorQuery->where('faculty_member_id', $facultyMember->id);
                        });
                    });
                })
                ->with(['targets.semesterCourse.semester', 'targets.semesterCourse.course']);

            return DataTables::of($deployedQuestionnairesQuery)
                ->addIndexColumn()
                ->addColumn('name', fn($questionnaire) => $questionnaire->name ?? 'N/A')
                ->addColumn('status', fn($questionnaire) => $questionnaire->status ?? 'N/A')
                ->addColumn('completion_rate', fn($questionnaire) => 
                    $this->unitOfWork->deployedQuestionnaires()->completionRate($questionnaire->id) . '%' ?? 'N/A')
                ->addColumn('semester', fn($questionnaire) => 
                    $questionnaire->targets->first()?->semesterCourse?->semester?->name ?? 'N/A')
                ->addColumn('course', fn($questionnaire) => 
                    $questionnaire->targets->first()?->semesterCourse?->course?->name ?? 'N/A')
                ->addColumn('average_rate', function($questionnaire) {
                    try {
                        $stats = $this->unitOfWork->responses()->getResponsesQuestionsByQuestionnaire($questionnaire->id);
                        return $stats['overall_stats']['likert_average'] ?? 'N/A';
                    } catch (\Exception $e) {
                        return 'N/A';
                    }
                })
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
            logError('Table data error', 'FacultyMemberService', $e);
            throw new ServiceException('Unable to load faculty member-specific table data due to system error', 0, $e);
        }
    }

}
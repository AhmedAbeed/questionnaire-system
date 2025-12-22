<?php

namespace App\Services;

use Yajra\DataTables\Facades\DataTables;
use Exception;
use App\Exceptions\ServiceException;

class FacultyService extends BaseService
{
    /**
     * Get faculty statistics for dashboard cards.
     *
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            
            $facultyRepo = $this->unitOfWork->faculties();
            $programRepo = $this->unitOfWork->programs();

            return [
                'total_faculties' => [
                    'value' => formatNumber($facultyRepo->count(), 0),
                    'updated' => formatDate($facultyRepo->latestUpdateTime()),
                ],
                'total_programs' => [
                    'value' => formatNumber($programRepo->count(), 0),
                    'updated' => formatDate($programRepo->latestUpdateTime()),
                ]
            ];
        } catch (Exception $e) {
            logError('Failed to fetch faculty statistics', 'FacultyService', $e);
            throw new ServiceException('Unable to retrieve faculty statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for faculties.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->faculties()->query()->with(['programs.students','facultyMembers']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('total_programs', fn($faculty) => $faculty->programs->count())
                ->addColumn('total_students', fn($faculty) => $faculty->programs->pluck('students')->flatten()->count())
                ->addColumn('total_faculty_members',fn($faculty) => $faculty->facultyMembers->count())
                ->addColumn('created_at', fn($faculty) => formatDate($faculty->created_at))
                ->addColumn('actions', fn($faculty) => $this->getActionButtons($faculty))
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to fetch dataTable data', 'FacultyService', $e);
            throw new ServiceException('Unable to load faculty data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for faculty.
     *
     * @param Faculty $faculty
     */
    private function getActionButtons($faculty): string 
    {
        return '<div class="btn-group">
            <a href="javascript:void(0)" 
               class="btn btn-outline-danger rounded ms-2 delete-faculty" 
               data-id="' . $faculty->id . '"
               title="حذف">
                <i class="fa fa-trash"></i>
            </a>
        </div>';
    }


    /**
     * Delete a faculty by ID.
     *
     * @param int $id
     * @throws ServiceException|BusinessValidationException
     */
    public function delete(int $id): void
    {
        try {
            $faculty = $this->unitOfWork->faculties()->find($id);
            if (!$faculty) {
                throw new BusinessValidationException('Faculty not found', 404);
            }
            $this->unitOfWork->faculties()->delete($id);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to delete faculty', 'FacultyService', $e);
            throw new ServiceException('Unable to delete faculty due to system error', 0, $e);
        }
    }

    /**
     * Get all faculties.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ServiceException
     */
    public function all()
    {
        try {
            return $this->unitOfWork->faculties()->all();
        } catch (Exception $e) {
            logError('Failed to retrieve all faculties', 'FacultyService', $e);
            throw new ServiceException('Unable to retrieve faculties due to system error', 0, $e);
        }
    }
}
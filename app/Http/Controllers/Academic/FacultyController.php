<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\FacultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Exceptions\PermissionDeniedException;


class FacultyController extends Controller
{
    public function __construct
    (
        private FacultyService $facultyService
    )
    {}

    /**
     * Display faculty overview
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('faculty.view');
            return view('academic.faculties.index');
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Get faculty data for DataTable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('faculty.dataTable');
            return $this->facultyService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Get faculty statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('faculty.stats');
            $stats = $this->facultyService->getStats();
            return successResponse(__("Data loaded successfully."), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("An error occurred."), 500);
        }
    }

    /**
     * Delete a faculty
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->checkPermission('faculty.delete');
            $this->facultyService->delete($id);
            return successResponse(__("Faculty deleted successfully."));
        } catch (BusinessException $e) {
            return errorResponse(__("A business error occurred."), 500);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("An error occurred."), 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\CourseService; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\BusinessException;
use App\Exceptions\PermissionDeniedException;


class CourseController extends Controller
{
    public function __construct
    (
        private CourseService $courseService
    )
    {}

    /**
     * Display course overview
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('course.view');
            return view('academic.courses.index');
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Display specific course details
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show(int $id): View|RedirectResponse
    {
        try {
            $this->checkPermission('course.view');
            $course = $this->courseService->find($id);
            $course->student_counts = $this->courseService->countStudents($course);
            return view('academic.courses.show', compact('course'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            logError('Error fetching course details', 'CourseController', $e);
            return view('errors.505');
        }
    }

    /**
     * Get course data for DataTable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('course.dataTable');
            return $this->courseService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            logError('Error fetching course data table', 'CourseController', $e);
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Get course-specific data for DataTable
     *
     * @param int $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function courseSpecificDataTable($course): JsonResponse
    {
        try {
            $this->checkPermission('course.dataTable');
            $course = $this->courseService->find($course);
            return $this->courseService->questionnaireDataTable($course);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            logError('Error fetching course-specific data table', 'CourseController', $e);
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Get course statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('course.stats');
            $stats = $this->courseService->getStats();
            return successResponse(__("Data loaded successfully."), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            logError('Error fetching course statistics', 'CourseController', $e);
            return errorResponse(__("An error occurred."), 500);
        }
    }

    /**
     * Delete a course
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->checkPermission('course.delete');
            $this->courseService->delete($id);
            return successResponse(__("Course deleted successfully."));
        } catch (BusinessException $e) {
            return errorResponse(__("A business error occurred."), 500);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            logError('Error deleting course', 'CourseController', $e);
            return errorResponse(__("An error occurred."), 500);
        }
    }
}
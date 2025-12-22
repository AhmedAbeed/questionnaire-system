<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Http\{Request,JsonResponse};
use App\Models\{Enrollment};
use App\Imports\{EnrollmentsImport,InstructorEnrollmentImport};
use App\Services\EnrollmentService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\{BusinessException, BusinessValidationException, ServiceException, PermissionDeniedException};


class EnrollmentController extends Controller
{
    /**
     * @param EnrollmentService $enrollmentService
     */
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {
    }

    /**
     * Display the enrollment management page
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('enrollment.view');
            $dashboardData = $this->enrollmentService->getDashboardData();
            $semesters = $dashboardData['semesters'];
            $courses = $dashboardData['courses'];
            $faculties = $dashboardData['faculties'];
            $programs = $dashboardData['programs'];
            return view('academic.enrollments.index', compact('semesters', 'courses', 'faculties', 'programs'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (ServiceException $e) {
            logError('Error fetching enrollment data', 'EnrollmentController', $e);
            abort(500);
        }
    }

    /**
     * Import student enrollments from file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.import');
            $request->validate([
                'file' => 'required|mimes:csv,xlsx,xls',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $enrollments = $this->getEnrollmentRecordsFromFile($request, 'student');
            
            $data = $this->enrollmentService->importStudentEnrollments($enrollments, $request->semester_id, auth()->id());

            return successResponse(__("Data loaded successfully."), $data);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Failed to import enrollments', 'EnrollmentController', $e, [
                'file' => $request->file('file')?->getClientOriginalName(),
                'semester_id' => $request->semester_id
            ]);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Get enrollment records from uploaded file
     * 
     * @param Request $request
     * @param string $type 'student' or 'instructor'
     * @return \Illuminate\Support\Collection
     */
    private function getEnrollmentRecordsFromFile(Request $request, string $type)
    {
        switch ($type) {
            case 'instructor':
                $import = new InstructorEnrollmentImport();
                Excel::import($import, $request->file('file'));
                return $import->getInstructorEnrollments();
                
            case 'student':
            default:
                $import = new EnrollmentsImport();
                Excel::import($import, $request->file('file'));
                return $import->getEnrollments();
        }
    }

    /**
     * Import instructor enrollments from file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function importInstructor(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.import');
            $request->validate([
                'file' => 'required|mimes:csv,xlsx,xls',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $instructorEnrollments = $this->getEnrollmentRecordsFromFile($request, 'instructor');
            
            $data = $this->enrollmentService->importInstructorEnrollments($instructorEnrollments, $request->semester_id, auth()->id());

            return successResponse(__("Data loaded successfully."), $data);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Failed to import instructor enrollments', 'EnrollmentController', $e, [
                'file' => $request->file('file')?->getClientOriginalName(),
                'semester_id' => $request->semester_id
            ]);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Get enrollment data for DataTable
     * 
     * @return JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.dataTable');
            return $this->enrollmentService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error fetching enrollment data table', 'EnrollmentController', $e);
            return errorResponse(__("Error fetching data for data table."), $e->getCode());
        }
    }

    /**
     * Get progress of an enrollment import task
     * 
     * @param string $taskId
     * @return JsonResponse
     */
    public function progress(string $taskId): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.progress');
            $data = $this->enrollmentService->getTaskProgress($taskId);
            return successResponse(__("Task progress"), $data);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error fetching task progress', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Get enrollment statistics
     * 
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.stats');
            $stats = $this->enrollmentService->getStats();
            return successResponse(__("Data loaded successfully."), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error fetching enrollment statistics', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Store a single enrollment record
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.create');
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'course_id' => 'required|exists:courses,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $enrollment = $this->enrollmentService->createSingleEnrollment(
                $request->student_id,
                $request->course_id,
                $request->semester_id
            );

            return successResponse(__("Enrollment created successfully."), $enrollment);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error creating single enrollment', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Get students for Select2 dropdown
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudents(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.view');
            $search = $request->input('search');
            $data = $this->enrollmentService->getStudentsForSelect2($search);
            return response()->json($data);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error fetching students for Select2', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Get courses for Select2 dropdown
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCourses(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.view');
            $search = $request->input('search');
            $data = $this->enrollmentService->getCoursesForSelect2($search);
            return response()->json($data);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error fetching courses for Select2', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }

    /**
     * Delete an enrollment record
     * 
     * @param Enrollment $enrollment
     * @return JsonResponse
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        try {
            $this->checkPermission('enrollment.delete');
            $this->enrollmentService->deleteEnrollment($enrollment);
            return successResponse(__("Enrollment deleted successfully."));
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), $e->getCode());
        } catch (ServiceException $e) {
            logError('Error deleting enrollment and responses', 'EnrollmentController', $e);
            return errorResponse(__("An error occurred."), $e->getCode());
        }
    }
}
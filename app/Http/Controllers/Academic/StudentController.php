<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\StudentService; 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\BusinessValidationException;
use Exception;

class StudentController extends Controller
{
    public function __construct(private StudentService $studentService)
    {}

    /**
     * Display student statistics and overview
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('student.view');
            return view('academic.student.index');
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Display specific student details
     * @param int $id
     */
    public function show($id): View|RedirectResponse
    {
        try {
            $this->checkPermission('student.show');
            $student = $this->studentService->find($id);
            return view('academic.student.show', compact('student'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (Exception $e) {
            abort(500);
        }
    }

    /**
     * Get student data for DataTable
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('student.dataTable');
            return $this->studentService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Get student-specific data for DataTable
     * @param int $student
     */
    public function studentSpecificDataTable($student): JsonResponse
    {
        try {
            $this->checkPermission('student.dataTable');
            $student = $this->studentService->find($student);
            return $this->studentService->getStudentSpecificDataTable($student);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Get students for Select2 dropdown
     *
     * @param Request $request
     */
    public function getStudents(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('student.search');
            $search = $request->input('search');
            $query = $this->studentService->query()->with(['user', 'program.faculty'])
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

            return successResponse(__("Data loaded successfully."), [
                'results' => $students,
                'pagination' => [
                    'more' => false
                ]
            ]);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("An error occurred."), 500);
        }
    }

    /**
     * Get student statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('student.stats');
            $stats = $this->studentService->getStats();
            return successResponse(__("Data loaded successfully."), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("An error occurred."), 500);
        }
    }

    /**
     * Delete a student
     *
     * @param int $id
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->checkPermission('student.delete');
            $this->studentService->delete($id);
            return successResponse(__("Student deleted successfully."));
        } catch (BusinessValidationException $e) {
            return errorResponse(__("A business error occurred."), 500);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (Exception $e) {
            return errorResponse(__("An error occurred."), 500);
        }
    }
}
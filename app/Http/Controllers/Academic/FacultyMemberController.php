<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\FacultyMemberService; 
use Illuminate\Http\Request;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\BusinessValidationException;

class FacultyMemberController extends Controller
{
    public function __construct
    (
        private FacultyMemberService $facultyMemberService
    )
    {}

    /**
     * Display faculty member statistics and overview
     *
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('facultyMember.view');
            return view('academic.faculty-member.index');
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    /**
     * Display specific faculty member details
     *
     * @param int $id
     */
    public function show(int $id): View|RedirectResponse
    {
        try {
            $this->checkPermission('facultyMember.view');
            $facultyMember = $this->facultyMemberService->find($id);
            return view('academic.faculty-member.show', compact('facultyMember'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    /**
     * Get faculty member data for DataTable
     *
     * @return JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('facultyMember.dataTable');
            return $this->facultyMemberService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('An error occurred'), 500);
        }
    }

    /**
     * Get faculty member-specific data for DataTable
     *
     * @param int $facultyMember
     * @return JsonResponse
     */
    public function facultyMemberSpecificDataTable(int $facultyMember): JsonResponse
    {
        try {
            $this->checkPermission('facultyMember.dataTable');
            $facultyMemberObj = $this->facultyMemberService->find($facultyMember);
            return $this->facultyMemberService->getFacultyMemberSpecificDataTable($facultyMemberObj);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('An error occurred'), 500);
        }
    }

    /**
     * Get faculty members for Select2 dropdown
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFacultyMembers(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('facultyMember.select');
            $search = $request->input('search');
            $query = $this->facultyMemberService->query()->with(['user', 'faculty'])
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

            $facultyMembers = $query->get()->map(function ($facultyMember) {
                return [
                    'id' => $facultyMember->id,
                    'text' => $facultyMember->user->full_name . ' (' . $facultyMember->academic_id . ')',
                    'faculty_member_id' => $facultyMember->academic_id,
                    'name' => $facultyMember->user->full_name,
                    'faculty_name' => $facultyMember->faculty->name,
                    'position' => $facultyMember->position
                ];
            });

            return successResponse('تم تحميل البيانات بنجاح', 200, [
                'results' => $facultyMembers,
                'pagination' => [
                    'more' => false
                ]
            ]);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('An error occurred'), 500);
        }
    }

    /**
     * Get faculty member statistics
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('facultyMember.stats');
            $stats = $this->facultyMemberService->getStats();
            return successResponse(__('Data loaded successfully.'), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('An error occurred'), 500);
        }
    }
}
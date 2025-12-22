<?php   
namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Faculty;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ServiceException;

class AdminController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index()
    {
        try {
            $faculties = Faculty::all();
            return view('user-management.admin', compact('faculties'));
        } catch (\Exception $e) {
            logError('Error fetching faculties', 'AdminController', $e);
               abort(500);

        }
    }

    public function dataTable()
    {
        try {
           return $this->userService->getDataTable();
        } catch (\Exception $e) {
            logError('Error fetching admin data table', 'AdminController', $e);
            return errorResponse(message:'حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.', status: 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|in:admin,faculty_dean,quality_manager',
                'faculty_id' => 'nullable|required_if:role,faculty_dean|exists:faculties,id',
            ]);
            
            $user = $this->userService->create($validated);
            return successResponse('تم إضافة المستخدم بنجاح', data: ['user' => $user]);
        } catch (\Exception $e) {
            logError('Error creating user', 'AdminController', $e);
            return errorResponse('حدث خطأ في النظام. يرجى المحاولة مرة أخرى.', 500);
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
            $stats = $this->userService->stats();
            return successResponse('Statistics retrieved successfully', data: $stats);
        } catch (\Exception $e) {
            logError('Error fetching course statistics', 'CourseController', $e);
            return errorResponse(__('An error occurred'), 500);
        }
    }

    /**
     * Reset the password for a user and send the new password by email
     */
    public function resetPassword(Request $request, $userId): JsonResponse
    {
        try {
            $this->userService->resetPasswordForAdminPanel($userId);
            return response()->json(['message' => 'تم إرسال كلمة مرور جديدة إلى المستخدم.']);
        } catch (\App\Exceptions\BusinessValidationException $e) {
            Log::error('Business validation error resetting user password: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Error resetting user password: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور.'], 500);
        }
    }
}
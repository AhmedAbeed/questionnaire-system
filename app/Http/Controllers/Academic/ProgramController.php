<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Http\Request;

class ProgramController extends Controller
{

    public function __construct
    (
        private ProgramService $programService
    )
    {}
    
    /**
     * Get programs by faculty ID
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByFaculty(Request $request)
    {
        try {
            $request->validate([
                'faculty_id' => 'required|exists:faculties,id'
            ]);
            $programs = $this->programService->findByFacultyId($request->faculty_id);
            
            return successResponse('تم تحميل البيانات بنجاح', 200, $programs);
        } catch (\Exception $e) {
            logError('Error fetching programs by faculty', 'ProgramController', $e);
            return errorResponse('Error fetching programs', status: 500);
        }
    }
} 
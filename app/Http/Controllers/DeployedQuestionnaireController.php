<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Exceptions\PermissionDeniedException;
use App\Exports\CourseQuestionnaireSummaryExport;
use App\Http\Requests\DeployQuestionnaireRequest;
use App\Models\Program;
use App\Models\Question;
use App\Models\QuestionnaireTargetType;
use App\Services\DeployedQuestionnaireService;
use App\Services\{EnrollmentService,SemesterService,CourseService,FacultyService,ProgramService};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class DeployedQuestionnaireController extends Controller
{
    /**
     * @param DeployedQuestionnaireService $deployedQuestionnaireService
     * @param EnrollmentService $enrollmentService
     * @param SemesterService $semesterService
     * @param CourseService $courseService
     * @param FacultyService $facultyService
     * @param ProgramService $programService
     */
    public function __construct(
        private DeployedQuestionnaireService $deployedQuestionnaireService,
        private EnrollmentService $enrollmentService,
        private SemesterService $semesterService,
        private CourseService $courseService,
        private FacultyService $facultyService,
        private ProgramService $programService
    ) {
    }
    

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.view');
            $semesters = $this->semesterService->all();
            $courses = $this->courseService->query()->with('faculty')->get();
            $faculties = $this->facultyService->all();
            $targetTypes = QuestionnaireTargetType::all();
            return view('deployed-questionnaire.index', compact('faculties', 'semesters','courses','targetTypes'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    /**
     * Display the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.create');
            $activeQestionnaireTemplates = $this->deployedQuestionnaireService->getActiveQuestionnaireTemplate();
            $semesters = $this->semesterService->all();
            $courses = $this->courseService->query()->with('faculty')->get();
            $faculties = $this->facultyService->all();
            $programs = Program::all();
            $questionnaireTargetTypes = QuestionnaireTargetType::all();
            $questions = Question::all();
            return view('deployed-questionnaire.create', compact('activeQestionnaireTemplates', 'faculties', 'programs', 'courses', 'semesters', 'questionnaireTargetTypes', 'questions', 'courses'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    /**
     * Get deployed questionnaire data for DataTable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.dataTable');
            return $this->deployedQuestionnaireService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            return errorResponse(__("Error fetching data for data table."), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeployQuestionnaireRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(DeployQuestionnaireRequest $request): JsonResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.create');
            $questionnaireTemplate = $this->deployedQuestionnaireService->deployQuestionnaires($request->all());
            return successResponse(__('Questionnaire deployed successfully.'));
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), 400);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            return errorResponse(__("System Error. Please try again later."), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function getDeployedQuestionnaire(int $id): View|RedirectResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.view');
            
            $questionnaireDeployed = $this->deployedQuestionnaireService->find($id);
            if (!$questionnaireDeployed) {
                return redirect()->back()->with('error', __('Questionnaire not found.'));
            }
            return view('respondent.questionnaire', compact('questionnaireDeployed'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('System Error. Please try again later.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit(int $id): View|RedirectResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.edit');
            $questionnaire = $this->deployedQuestionnaireService->find($id);
            return view('deployed-questionnaire.edit', compact('questionnaire'));
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), 500);
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('System Error. Please try again later.'));
        }
    }

    /**
     * Update the close date of the specified resource.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCloseDate(Request $request, int $id): JsonResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.edit');
            $validated = $request->validate([
                'close_date' => 'required|date|after:now',
            ]);

            $this->deployedQuestionnaireService->updateCloseDate($id, $validated['close_date']);
            return successResponse(__('Questionnaire close date updated successfully.'));
        } catch (BusinessException $e) {
            return errorResponse($e->getMessage(), 500);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            return errorResponse(__("System Error. Please try again later."), 500);
        }
    }

    /**
     * Export non-responding students data.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportNonRespondingStudents(Request $request)
    {
        try {
            $this->checkPermission('deployed-questionnaire.export');
            $request->validate([
                'faculty_id' => 'required|exists:faculties,id',
                'semester_id' => 'required|exists:semesters,id',
            ]);

            $export = $this->deployedQuestionnaireService->exportNonRespondingStudents(
                $request->faculty_id,
                $request->semester_id
            );

            return Excel::download(
                $export,
                'non-responding-students-' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to export non-responding students data.'));
        }
    }

    /**
     * Get statistics for deployed questionnaires dashboard (AJAX endpoint)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.stats');
            $stats = $this->deployedQuestionnaireService->getStats();
            return successResponse(__("Statistics retrieved successfully."), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            return errorResponse(__("Error fetching deployed questionnaire statistics."), 500);
        }
    }

    /**
     * Export the course questionnaire summary as an Excel file.
     *
     * @param int $questionnaireId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCourseQuestionnaireSummary(int $questionnaireId)
    {
        try {
            $this->checkPermission('deployed-questionnaire.export');
            $questionnaire = $this->deployedQuestionnaireService->find($questionnaireId);

            $summary = $this->deployedQuestionnaireService->getQuestionnaireSummary($questionnaireId);

            $courseName = optional($questionnaire->targets->first()->semesterCourse->course)->name ?? 'course';
            $fileName = $courseName . '-summary-' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new CourseQuestionnaireSummaryExport($summary),
                $fileName
            );
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to export course questionnaire summary.'));
        }
    }

    /**
     * Return programs by faculty for AJAX filter
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgramsByFaculty(Request $request): JsonResponse
    {
        try {
            $this->checkPermission('deployed-questionnaire.view');
            $facultyId = $request->input('faculty_id');
            if (!$facultyId) {
                return errorResponse(__("Faculty ID is required."), 400);
            }
            $programs = $this->programService->findByFacultyId($facultyId);
            return successResponse(__("Programs retrieved successfully."), $programs);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__("Permission denied."), 403);
        } catch (\Exception $e) {
            return errorResponse(__("Error fetching programs."), 500);
        }
    }
}
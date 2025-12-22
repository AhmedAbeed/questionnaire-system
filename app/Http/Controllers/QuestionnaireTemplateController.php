<?php

namespace App\Http\Controllers;

use App\Services\QuestionnaireTemplateService;
use App\Models\Question;
use App\Http\Requests\StoreQuestionnaireTemplateRequest;
use App\Exceptions\BusinessValidationException;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class QuestionnaireTemplateController extends Controller
{
    public function __construct(
        private QuestionnaireTemplateService $questionnaireTemplateService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.view');
            return view('questionnaire-template.index');
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    /**
     * Get DataTable data for questionnaire templates.
     */
    public function dataTable(): JsonResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.dataTable');
            return $this->questionnaireTemplateService->getDataTable();
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('Error fetching data for data table.'), 500);
        }
    }

    /**
     * Show the form for creating a new questionnaire template.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.create');
            $questions = Question::with('type', 'category', 'options')->get();
            return view('questionnaire-template.create', compact('questions'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating questionnaire template');
        }
    }

    /**
     * Store a newly created questionnaire template.
     */
    public function store(StoreQuestionnaireTemplateRequest $request): JsonResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.create');
            $this->questionnaireTemplateService->createTemplate($request->validated());
            return successResponse(__('Questionnaire template created successfully'));
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return errorResponse(__('Error creating questionnaire template, please try again later'), 500);
        }
    }

    /**
     * Show a single questionnaire template (view page)
     */
    public function show(int $id): View|RedirectResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.view');
            $template = $this->questionnaireTemplateService->getQuestionnaireTemplate($id);
            if (!$template) {
                abort(404, 'Template not found');
            }
            $numQuestions = $template->templateQuestions->count();
            $questionTypeStats = $template->templateQuestions
                ->groupBy(fn($tq) => $tq->question->type->name ?? 'غير معروف')
                ->map(fn($group) => $group->count());
            $deployedCount = $template->deployedQuestionnaires->count();
            return view('questionnaire-template.show', compact('template', 'numQuestions', 'questionTypeStats', 'deployedCount'));
        } catch (PermissionDeniedException $e) {
            abort(403);
        } catch (\Exception $e) {
            abort(500, 'System Error. Please try again later.');
        }
    }

    /**
     * Get questionnaire template data as JSON for API
     */
    public function getTemplateData(int $id): JsonResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.view');
            $template = $this->questionnaireTemplateService->getQuestionnaireTemplate($id);
            
            if (!$template) {
                return errorResponse(__('Template not found.'), 404);
            }

            $templateData = [
                'name' => $template->name,
                'description' => $template->description,
                'data' => [
                    'template_questions' => $template->templateQuestions->map(function($templateQuestion) {
                        return [
                            'question' => [
                                'id' => $templateQuestion->question->id,
                                'text' => $templateQuestion->question->text,
                                'type' => [
                                    'id' => $templateQuestion->question->type->id,
                                    'name' => $templateQuestion->question->type->name
                                ]
                            ],
                            'is_required' => (bool) $templateQuestion->is_required,
                            'order' => $templateQuestion->order
                        ];
                    })
                ]
            ];

            return successResponse(__('Template data retrieved successfully.'), $templateData);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('Error retrieving template data.'), 500);
        }
    }

    /**
     * Get questionnaire template statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.stats');
            $stats = $this->questionnaireTemplateService->getStats();
            return successResponse(__('Statistics retrieved successfully'), $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('Error fetching statistics'), 500);
        }
    }

    /**
     * Delete a questionnaire template
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->checkPermission('questionnaireTemplate.delete');
            $this->questionnaireTemplateService->delete($id);
            return successResponse(__('Questionnaire template deleted successfully.'));
        } catch (BusinessValidationException $e) {
            return errorResponse($e->getMessage(), 400);
        } catch (PermissionDeniedException $e) {
            return errorResponse(__('Permission denied.'), 403);
        } catch (\Exception $e) {
            return errorResponse(__('An error occurred.'), 500);
        }
    }
}

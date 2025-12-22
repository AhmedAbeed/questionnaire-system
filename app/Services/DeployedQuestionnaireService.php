<?php

namespace App\Services;

use App\Exceptions\{BusinessValidationException, ServiceException};
use App\Models\{DeployedQuestionnaire, Faculty, DeployedQuestion, DeployedQuestionOption, Question, Student, Program, SemesterCourse, QuestionnaireTemplate};
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Services\Business\CourseQuestionnaireSummaryService;

class DeployedQuestionnaireService extends BaseService
{

    public function getQuestionnaireSummary($questionnaireId)
    {
        $questionnaire = $this->find($questionnaireId);
        
        if (!$questionnaire) {
            throw new \Exception('Questionnaire not found');
        }

        $summaryService = new CourseQuestionnaireSummaryService($this->unitOfWork, $questionnaire);
        return $summaryService->generateSummary();
    }


    public function getActiveQuestionnaireTemplate()
    {
        try {
            return $this->unitOfWork->questionnaireTemplates()->getActiveQuestionnaireTemplates();
        } catch (BusinessValidationException $e) {
            logError('Failed to fetch active questionnaire templates', 'DeployedQuestionnaireService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to fetch active questionnaire templates', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to retrieve active questionnaire templates due to system error', 0, $e);
        }
    }

    /**
     * Deploy a single questionnaire.
     *
     * @param array $data
     * @throws ServiceException|BusinessValidationException
     */
    public function deployQuestionnaire(array $data)
    {
        try {
            $this->checkForDuplicateQuestionnaire($data);
            
            return DB::transaction(function () use ($data) {
                $questionnaire = $this->unitOfWork->deployedQuestionnaires()->create($data['questionnaire']);
                $this->createDeployedTargets($data['target'] ?? [], $questionnaire->id);
                $this->createDeployedQuestions($data['questions'] ?? [], $questionnaire->id);
                return $questionnaire;
            });
            
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to deploy questionnaire', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to deploy questionnaire due to system error', 0, $e);
        }
    }

    /**
     * Deploy multiple questionnaires based on strategy.
     *
     * @param array $questionnaireData
     * @throws ServiceException|BusinessValidationException
     */
    public function deployQuestionnaires(array $questionnaireData)
    {
        try {
            $results = []; 
            $questionnaire = $this->formatQuestionnaireData($questionnaireData);
            $formattedQuestions = $this->formatQuestions($questionnaireData['questionnaire']['questions'] ?? []);
            $targets = $this->processTargetData($questionnaireData['questionnaire']['target'] ?? []);
            $formattedTargets = $this->formatTargets($targets);

            $deploymentStrategy = $questionnaireData['questionnaire']['settings']['deployment_strategy'] ?? null;
            
            if ($deploymentStrategy === 'single') {
                $data = [
                    'questionnaire' => $questionnaire,
                    'questions' => $formattedQuestions,
                    'target' => $formattedTargets
                ];
                $results[] = $this->deployQuestionnaire($data);
            } else if ($deploymentStrategy === 'per_target') {
                foreach ($formattedTargets as $target) {
                    $data = [
                        'questionnaire' => $questionnaire,
                        'questions' => $formattedQuestions,
                        'target' => [$target]
                    ];
                    $results[] = $this->deployQuestionnaire($data);
                }
            } else {
                throw new BusinessValidationException(__('Invalid deployment strategy.'));
            }
            
            return $results;
            
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to deploy questionnaires', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to deploy questionnaires due to system error', 0, $e);
        }
    }

    /**
     * Create deployed questions.
     *
     * @param array $questions
     * @param int $questionnaireId
     */
    private function createDeployedQuestions(array $questions, int $questionnaireId): void 
    {
        foreach ($questions as $index => $question) {
            $question['questionnaire_id'] = $questionnaireId;
            $deployedQuestion = $this->unitOfWork->deployedQuestions()->create($question);
            $this->createDeployedOptions($question['options'] ?? [], $deployedQuestion->id);
        }
    }

    /**
     * Create deployed options.
     *
     * @param array $options
     * @param int $deployedQuestionId
     */
    private function createDeployedOptions(array $options, int $deployedQuestionId): void
    {
        $deployedQuestion = DeployedQuestion::with('question.type')->find($deployedQuestionId);
        
        if (!$deployedQuestion) {
            throw new BusinessValidationException(__('Deployed question not found.'), 404);
        }

        if ($deployedQuestion->question->type->name === "Instructor Select") {
            $this->handleInstructorOptions($deployedQuestionId);
            return;
        }

        foreach ($options as $index => $option) {
            $this->unitOfWork->deployedQuestionOption()->create([
                'deployed_question_id' => $deployedQuestionId,
                'option_id' => $option['option_id'],
                'option_text' => $option['option_text'],
                'order' => $option['order'] ?? ($index + 1),
                'value' => $option['value']
            ]);
        }
    }

    /**
     * Handle instructor options.
     *
     * @param int $deployedQuestionId
     */
    private function handleInstructorOptions(int $deployedQuestionId): void
    {
        $deployedQuestion = DeployedQuestion::with(['questionnaire.targets.semesterCourse.instructors.facultyMember'])
            ->find($deployedQuestionId);

        if (!$deployedQuestion) {
            throw new BusinessValidationException(__('Deployed question not found.'), 404);
        }

        $target = $deployedQuestion->questionnaire->targets()
            ->whereNotNull('semester_course_id')
            ->first();

        if (!$target || !$target->semesterCourse) {
            throw new BusinessValidationException(__('No semester course target found for instructor options.'), 404);
        }

        $instructors = $target->semesterCourse->instructors()
            ->where('status', 'active')
            ->with(['facultyMember.user' => function($query) {
                $query->select('id', 'name', 'email');
            }])
            ->orderBy('is_primary', 'desc')
            ->get();

        foreach ($instructors as $index => $instructor) {
            $this->unitOfWork->deployedQuestionOption()->create([
                'deployed_question_id' => $deployedQuestionId,
                'option_id' => $instructor->id,
                'option_text' => $instructor->facultyMember->user->name,
                'order' => $index + 1,
                'value' => $instructor->id
            ]);
        }
    }

    /**
     * Create deployed targets.
     *
     * @param array $targets
     * @param int $questionnaireId
     */
    private function createDeployedTargets(array $targets, int $questionnaireId): void
    {
        foreach ($targets as $target) {
            $this->unitOfWork->deployedQuestionnaireTargets()->create(
                array_merge($target, ['questionnaire_id' => $questionnaireId])
            );
        }
    }

    /**
     * Check for duplicate questionnaire.
     *
     * @param array $data
     * @throws BusinessValidationException
     */
    protected function checkForDuplicateQuestionnaire(array $data): void
    {
        $existingQuestionnaire = DeployedQuestionnaire::where('name', $data['questionnaire']['name'])
            ->whereHas('targets', function ($query) use ($data) {
                $targetData = collect($data['target']);
                
                $query->where(function ($q) use ($targetData) {
                    foreach ($targetData as $target) {
                        $q->orWhere(function ($subQ) use ($target) {
                            if (!empty($target['faculty_id'])) {
                                $subQ->where('faculty_id', $target['faculty_id']);
                            }
                            if (!empty($target['program_id'])) {
                                $subQ->where('program_id', $target['program_id']);
                            }
                            if (!empty($target['semester_course_id'])) {
                                $subQ->where('semester_course_id', $target['semester_course_id']);
                            }
                        });
                    }
                });
            })
            ->first();

        if ($existingQuestionnaire) {
            throw new BusinessValidationException(__('A questionnaire with the same name and targets already exists.'), 400);
        }
    }

    /**
     * Format questions.
     *
     * @param array $questions
     * @param int|null $questionnaireId
     * @return array
     * @throws BusinessValidationException
     */
    public function formatQuestions(array $questions, ?int $questionnaireId = null): array
    {
        $formatted = [];
        foreach ($questions as $key => $question) {
            $questionModel = Question::find($question['id'] ?? null);
            if (!$questionModel) {
                throw new BusinessValidationException(__('Question not found.'), 404);
            }
            $formatted[$key] = [
                'questionnaire_id' => $questionnaireId,
                'question_id' => $questionModel->id,
                'type' => $questionModel->type_id,
                'order' => $key + 1,
                'is_required' => $question['required'] ?? false,
                'category' => $questionModel->category_id,
                'text' => $questionModel->text,
                'description' => $questionModel->description,
                'options' => $questionModel->hasOptions() ? $questionModel->options()->get()->map(function($option) {
                    return [
                        'option_id' => $option->id,
                        'option_text' => $option->option_text,
                        'value' => $option->value
                    ];
                })->toArray() : [],
            ];
        }
        return $formatted;
    }

    /**
     * Format options.
     *
     * @param mixed $question
     * @param int|null $deployedQuestionId
     * @return array
     */
    protected function formatOptions($question, ?int $deployedQuestionId = null): array
    {
        if (!$question->hasOptions()) {
            return [];
        }
        
        $options = $question->options()->get();
        if ($options->isEmpty()) {
            return [];
        }
        
        return $options->map(function($option) use ($deployedQuestionId) {
            return [
                'deployed_question_id' => $deployedQuestionId,
                'option_id' => $option->id,
                'option_text' => $option->option_text,
                'value' => $option->value
            ];
        })->toArray();
    }

    /**
     * Process target data.
     *
     * @param array $targetData
     * @return array
     * @throws BusinessValidationException
     */
    public function processTargetData(array $targetData): array
    {
        if (empty($targetData)) {
            return [];
        }
        
        $type = $targetData['type'] ?? null;
        $scope = $targetData['scope'] ?? null;
        $role = $targetData['role'] ?? null;
        $data = $targetData['data'] ?? null;

        if (empty($type) || empty($scope) || empty($data)) {
            throw new BusinessValidationException(__('Invalid target data. Missing required fields.'), 400);
        }

        if ($scope === 'academic') {
            return $this->processAcademicTargets($data);
        }

        throw new BusinessValidationException(__('Unsupported target scope.'), 400);
    }

    /**
     * Process academic targets.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processAcademicTargets(array $data): array
    {
        $method = $data['method'] ?? null;

        if (empty($method)) {
            throw new BusinessValidationException(__('Target method is required.'), 400);
        }

        switch ($method) {
            case 'faculty':
                return $this->processFacultyTarget($data);
            case 'program':
                return $this->processProgramTarget($data);
            case 'course':
                return $this->processCourseTarget($data);
            default:
                throw new BusinessValidationException(__('Invalid target method.'), 400);
        }
    }

    /**
     * Process faculty target.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processFacultyTarget(array $data): array
    {
        $facultyIds = $data['faculty_ids'] ?? [];
        
        if (empty($facultyIds)) {
            throw new BusinessValidationException(__('Faculty IDs are required.'), 400);
        }
        
        $faculties = Faculty::whereIn('id', $facultyIds)->get();
        
        if ($faculties->count() !== count($facultyIds)) {
            throw new BusinessValidationException(__('One or more selected faculties do not exist.'), 400);
        }
        
        return $faculties->map(function ($faculty) {
            return [
                'faculty_id' => $faculty->id,
                'program_id' => null,
                'semester_course_id' => null
            ];
        })->toArray();
    }

    /**
     * Process program target.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processProgramTarget(array $data): array
    {
        $programIds = $data['program_ids'] ?? [];
        
        if (empty($programIds)) {
            throw new BusinessValidationException(__('Program IDs are required.'), 400);
        }
        
        $programs = Program::whereIn('id', $programIds)->get();
        
        if ($programs->count() !== count($programIds)) {
            throw new BusinessValidationException(__('One or more selected programs do not exist.'), 400);
        }
        
        return $programs->map(function ($program) {
            return [
                'faculty_id' => $program->faculty_id,
                'program_id' => $program->id,
                'semester_course_id' => null
            ];
        })->toArray();
    }

    /**
     * Process course target.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processCourseTarget(array $data): array
    {
        $selectionType = $data['course_selection_type'] ?? null;

        if (empty($selectionType)) {
            throw new BusinessValidationException(__('Course selection type is required.'), 400);
        }

        switch ($selectionType) {
            case 'all':
                return $this->processAllCourseTarget($data);
            case 'specific':
                return $this->processSpecificCourseTarget($data);
            default:
                throw new BusinessValidationException(__('Invalid course selection type.'), 400);
        }
    }

    /**
     * Process specific course target.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processSpecificCourseTarget(array $data): array
    {
        $courseId = $data['course_id'] ?? null;
        $semesterId = $data['semester_id'] ?? null;

        if (empty($courseId) || empty($semesterId)) {
            throw new BusinessValidationException(__('Course ID and semester ID are required.'), 400);
        }

        $courseIds = is_array($courseId) ? $courseId : [$courseId];

        $semesterCourses = SemesterCourse::where('semester_id', $semesterId)
            ->whereIn('course_id', $courseIds)
            ->get();

        if ($semesterCourses->isEmpty()) {
            throw new BusinessValidationException(__('Selected courses are not available in this semester.'), 400);
        }

        return $semesterCourses->map(function ($semesterCourse) {
            return [
                'faculty_id' => $semesterCourse->faculty_id,
                'program_id' => $semesterCourse->program_id,
                'semester_course_id' => $semesterCourse->id
            ];
        })->toArray();
    }

    /**
     * Process all course target.
     *
     * @param array $data
     * @return array
     * @throws BusinessValidationException
     */
    protected function processAllCourseTarget(array $data): array
    {
        $facultyScope = $data['faculty_scope'] ?? null;
        $programScope = $data['program_scope'] ?? null;
        $semesterScope = $data['semester_scope'] ?? null;
        $selectedFaculties = $data['selected_faculties'] ?? [];
        $selectedPrograms = $data['selected_programs'] ?? [];
        $selectedSemesters = $data['selected_semesters'] ?? [];

        if (empty($facultyScope) || empty($programScope) || empty($semesterScope)) {
            throw new BusinessValidationException(__('All scope fields are required.'), 400);
        }

        $this->validateSelectedData($facultyScope, $selectedFaculties, 'faculties');
        $this->validateSelectedData($programScope, $selectedPrograms, 'programs');
        $this->validateSelectedData($semesterScope, $selectedSemesters, 'semesters');

        $query = SemesterCourse::query();

        if ($facultyScope === 'specific' && !empty($selectedFaculties)) {
            $query->whereHas('course', function ($query) use ($selectedFaculties) {
                $query->whereIn('faculty_id', $selectedFaculties);
            });
        }

        if ($semesterScope === 'specific' && !empty($selectedSemesters)) {
            $query->whereIn('semester_id', $selectedSemesters);
        }

        $courses = $query->get();

        if ($courses->isEmpty()) {
            throw new BusinessValidationException(__('No courses found matching the criteria.'), 400);
        }

        return $courses->map(function ($course) {
            return [
                'faculty_id' => $course->faculty_id,
                'program_id' => $course->program_id,
                'semester_course_id' => $course->id
            ];
        })->toArray();
    }

    /**
     * Validate selected data.
     *
     * @param string $scope
     * @param array $selectedData
     * @param string $type
     * @throws BusinessValidationException
     */
    protected function validateSelectedData(string $scope, array $selectedData, string $type): void
    {
        if ($scope === 'specific' && empty($selectedData)) {
            throw new BusinessValidationException(__("Selected {$type} are required when scope is specific."), 400);
        }
    }
    
    protected function logError(string $message, Exception $exception): void
    {
        Log::error($message, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    public function find(int $id)
    {
        try {
            return $this->unitOfWork->deployedQuestionnaires()->find($id);
        } catch (BusinessException $e) {
            logError('Failed to find questionnaire', 'DeployedQuestionnaireService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to find questionnaire', 'DeployedQuestionnaireService', $e);
            throw $e;
        }
    }

    public function getTableData(): array
    {
        try {
            $questions = $this->unitOfWork->deployedQuestionnaires()->all();
            return $this->prepareTableData($questions)->toArray();
        } catch (BusinessException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve table data', 'DeployedQuestionnaireService', $e);
            throw $e;
        }
    }


    public function getStats(): array
    {
        try {
            $deployedQuestionnairesRepo = $this->unitOfWork->deployedQuestionnaires();
            
            return [
                'total_deployed_questionnaires' => [
                    'value' => formatNumber($deployedQuestionnairesRepo->count(), 0),
                    'updated' => formatDate($deployedQuestionnairesRepo->latestUpdateTime()),
                ],
            ];
        } catch (Exception $e) {
            logError('Failed to fetch questionnaire statistics', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to retrieve questionnaire statistics due to system error', 0, $e);
        }
    }

    private function prepareTableData(Collection $deployedQuestionnaires): Collection
    {
        try {
            return $deployedQuestionnaires->map(function ($deployedQuestionnaire) {
                $actions = '<div class="btn-group" role="group">' 
                    . '<a href="' . route('response.report', $deployedQuestionnaire->id) . '" class="btn btn-sm btn-warning" title="' . __('view') . '">'
                    . '<i class="fa fa-eye"></i>'
                    . '</a>'
                    . '</div>';
                
                return [
                    'id' => $deployedQuestionnaire->id,
                    'name' => $deployedQuestionnaire->name ?? __('N/A'),
                    'description' => $deployedQuestionnaire->description ?? __('N/A'),
                    'target_type' => $deployedQuestionnaire->targetType->name ?? __('N/A'),
                    'target' => $deployedQuestionnaire->targets->first()->target_type ?? __('N/A'),
                    'status' => $deployedQuestionnaire->status ?? __('N/A'),
                    'completion_rate' => $deployedQuestionnaire->getCompletionRateAttribute() ?? __('N/A'),
                    'open_date' => $this->formatDate($deployedQuestionnaire->open_date),
                    'close_date' => $this->formatDate($deployedQuestionnaire->close_date),
                    'created_at' => $this->formatDate($deployedQuestionnaire->created_at),
                    'actions' => $actions
                ];
            });
        }
        catch (Exception $e) {
            logError('Failed to prepare table data', 'QuestionService');
            throw $e;
        }
    }

    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->deployedQuestionnaires()->query()
                ->with(['targetType', 'targets.semesterCourse.course', 'targets.semesterCourse.semester', 'creator']);

            // Apply filters
            $this->applyFilters($query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('target', fn($questionnaire) => $this->getTargetDisplay($questionnaire))
                ->editColumn('target_type.name', fn($questionnaire) => __($questionnaire->targetType->name) ?? __('N/A'))
                ->addColumn('semester', fn($questionnaire) => $questionnaire->targets->first()?->semesterCourse?->semester?->name ?? __('N/A'))
                ->addColumn('completion_rate', fn($questionnaire) => $questionnaire->getCompletionRateAttribute())
                ->editColumn('open_date', fn($questionnaire) => $this->formatDate($questionnaire->open_date))
                ->editColumn('close_date', fn($questionnaire) => $this->formatDate($questionnaire->close_date))
                ->editColumn('created_at', fn($questionnaire) => $this->formatDate($questionnaire->created_at))
                ->addColumn('actions', fn($questionnaire) => $this->getActionButtons($questionnaire))
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to fetch dataTable data', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to load questionnaire data due to system error', 0, $e);
        }
    }

    private function getTargetDisplay(DeployedQuestionnaire $questionnaire): string
    {
        $target = $questionnaire->targets->first();
        if (!$target) return __('N/A');

        if ($target->target_type === 'course' && $target->semesterCourse && $target->semesterCourse->course) {
            return $target->target_type . ' - ' . $target->semesterCourse->course->name . ' (' . $target->semesterCourse->course->code . ') ';
        }
        return $target->target_type;
    }

    private function formatDate(?Carbon $date): string
    {
        return formatDate($date);
           
    }

    private function getActionButtons(DeployedQuestionnaire $questionnaire): string 
    {
        $buttons = '<div class="btn-group">
            <a href="' . route('response.report', $questionnaire->id) . '" class="btn btn-outline-info rounded ms-2" title="' . __('view') . '">
                <i class="fa fa-eye"></i>
            </a>';

        if (!auth()->user()->hasRole('quality_manager')) {
            $buttons .= '<button type="button" class="btn btn-outline-warning rounded ms-2 edit-close-date" 
                data-id="' . $questionnaire->id . '"
                data-close-date="' . $questionnaire->close_date->format('Y-m-d\TH:i') . '"
                title="' . __('Edit Close Date') . '">
                <i class="fa fa-clock-o"></i>
            </button>';
        }

        $buttons .= '<a href="' . route('questionnaires.deployed.export-summary', $questionnaire->id) . '" class="btn btn-outline-success rounded ms-2" title="' . __('Download Report') . '">
                <i class="fa fa-download"></i>
            </a>
        </div>';

        return $buttons;
    }

    private function formatQuestionnaireData(array $questionnaireData): array
    {
        $templateType = $questionnaireData['questionnaire']['template']['type'] ?? null;
        $templateId = $questionnaireData['questionnaire']['template']['id'] ?? null;
        
        return [
            'template_id' => $templateType === 'template' ? $templateId : null,
            'name' => $questionnaireData['questionnaire']['settings']['name'] ?? null,
            'target_type_id' => $questionnaireData['questionnaire']['target']['type'] ?? null,
            'open_date' => $questionnaireData['questionnaire']['settings']['open_date'] ?? null,
            'close_date' => $questionnaireData['questionnaire']['settings']['close_date'] ?? null,
            'status' => $questionnaireData['questionnaire']['settings']['status'] ?? null,
            'creator_id' => auth()->user()->id ?? null,
        ];
    }

    private function formatTargets(array $targets): array
    {
        return array_map(function ($target) {
            return [
                'faculty_id' => $target['faculty_id'] ?? null,
                'program_id' => $target['program_id'] ?? null,
                'semester_course_id' => $target['semester_course_id'] ?? null
            ];
        }, $targets);
    }

    private function applyFilters(Builder $query): void
    {
        if (request()->has('name') && !empty(request('name'))) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }

        if (request()->has('target_type') && !empty(request('target_type'))) {
            $query->whereHas('targetType', function($q) {
                $q->where('id', request('target_type'));
            });
        }

        if (request()->has('course') && !empty(request('course'))) {
            $query->whereHas('targets.semesterCourse', function($q) {
                $q->whereHas('course', function($c) {
                    $c->where('id', request('course'));
                });
            });
        }

        if (request()->has('semester') && !empty(request('semester'))) {
            $query->whereHas('targets.semesterCourse', function($q) {
                $q->where('semester_id', request('semester'));
            });
        }

        if (request()->has('start_date') && !empty(request('start_date'))) {
            $query->whereDate('open_date', '>=', request('start_date'));
        }

        if (request()->has('end_date') && !empty(request('end_date'))) {
            $query->whereDate('close_date', '<=', request('end_date'));
        }

        if (request()->has('faculty') && !empty(request('faculty'))) {
            $query->where(function($query) {
                $query->whereHas('targets', function($q) {
                    $q->where('faculty_id', request('faculty'));
                })
                ->orWhereHas('targets.semesterCourse.course', function($q) {
                    $q->where('faculty_id', request('faculty'));
                });
            });
        }

        if (request()->has('program') && !empty(request('program'))) {
            $query->whereHas('targets', function($q) {
                $q->where('program_id', request('program'));
            });
        }
    }

    public function updateCloseDate(int $id, string $closeDate)
    {
        try {
            $questionnaire = $this->unitOfWork->deployedQuestionnaires()->find($id);
            
            if (!$questionnaire) {
                throw new BusinessException(__('Questionnaire not found.'), 404);
            }

            if ($questionnaire->status === 'closed') {
                throw new BusinessException(__('Cannot update close date of a closed questionnaire.'), 400);
            }

            $closeDateTime = Carbon::parse($closeDate);
            if ($closeDateTime->lte(now())) {
                throw new BusinessException(__('Close date must be in the future.'), 400);
            }

            $questionnaire->close_date = $closeDateTime;
            $questionnaire->save();

            return $questionnaire;
        } catch (BusinessException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to update questionnaire close date', 'DeployedQuestionnaireService', $e);
            throw $e;
        }
    }

    public function exportNonRespondingStudents(int $facultyId, int $semesterId)
    {
        try {
            $questionnaires = $this->getActiveQuestionnairesForExport($facultyId, $semesterId);
            $exportData = $this->prepareExportData($questionnaires);

            return new \App\Exports\NonRespondingStudentsExport($exportData);
        } catch (Exception $e) {
            logError('Failed to export non-responding students', 'DeployedQuestionnaireService', $e);
            throw new ServiceException('Unable to export non-responding students due to system error', 0, $e);
        }
    }

    private function getActiveQuestionnairesForExport(int $facultyId, int $semesterId): Collection
    {
        return $this->unitOfWork->deployedQuestionnaires()->query()
            ->with(['targets.semesterCourse.course', 'responses.user.student'])
            ->whereHas('targets', function ($query) use ($facultyId, $semesterId) {
                $query->whereHas('semesterCourse', function ($q) use ($facultyId, $semesterId) {
                    $q->where('semester_id', $semesterId)
                      ->whereHas('course', function ($c) use ($facultyId) {
                          $c->where('faculty_id', $facultyId);
                      });
                });
            })
            ->where('status', 'active')
            ->get();
    }

    private function prepareExportData(Collection $questionnaires): array
    {
        $exportData = [];
        
        foreach ($questionnaires as $questionnaire) {
            $enrolledStudents = Student::whereHas('enrollments', function ($query) use ($questionnaire) {
                $query->whereIn('semester_course_id', $questionnaire->targets->pluck('semester_course_id'));
            })->with(['user', 'program'])->get();

            $respondedUserIds = $questionnaire->responses->pluck('user_id');
            $nonRespondingStudents = $enrolledStudents->filter(function ($student) use ($respondedUserIds) {
                return !$respondedUserIds->contains($student->user_id);
            });

            foreach ($nonRespondingStudents as $student) {
                $exportData[] = [
                    'name' => $student->user->name,
                    'national_id' => $student->national_id,
                    'academic_id' => $student->academic_id,
                    'email' => $student->user->email,
                    'program' => $student->program->name,
                    'questionnaire_name' => $questionnaire->name,
                    'course' => $questionnaire->targets->first()->semesterCourse->course->name,
                    'course_code' => $questionnaire->targets->first()->semesterCourse->course->code,
                    'open_date' => $questionnaire->open_date->format('Y-m-d H:i:s'),
                    'close_date' => $questionnaire->close_date->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $exportData;
    }
}
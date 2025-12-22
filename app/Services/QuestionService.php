<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\QuestionCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Exceptions\BusinessException;
use Exception;
use App\Models\SemesterCourseInstructor;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class QuestionService extends BaseService
{
    /**
     * Create a new question.
     *
     * @param array $questions The questions to create.
     * @return Question
     */
    public function createQuestions(array $questions)
    {
        try {
            $this->unitOfWork->beginTransaction();
            
            $createdQuestions = [];
            foreach($questions as $question) {
                // Create the base question
                $createdQuestion = $this->createQuestion($question);
                
                // Handle options if they exist and it's not an instructor select question
                if (isset($question['options']) && is_array($question['options']) && !$this->isInstructorSelectQuestion($question)) {
                    foreach ($question['options'] as $index => $option) {
                        $this->createQuestionOption($createdQuestion->id, $option, $question['values'][$index] ?? null, $question['orders'][$index] ?? null);
                    }
                }
                $createdQuestions[] = $createdQuestion;
            }
            
            $this->unitOfWork->endTransaction();
            return $createdQuestions;
        } catch (BusinessValidationException $e) {
            $this->unitOfWork->rollback();
            throw $e;
        } catch (Exception $e) {    
            $this->unitOfWork->rollback();
            logError('Error creating question', 'QuestionService', $e);
            throw new ServiceException('Unable to create question due to system error', 0, $e);
        }
    }

    private function createQuestion(array $question)
    {
        return $this->unitOfWork->questions()->create([
            'text' => $question['text'],
            'description' => $question['description'] ?? null,
            'type_id' => $question['type_id'],
            'category_id' => $question['category_id'] ?? null,
        ]);
    }

    private function createQuestionOption(int $questionId, string $option, ?string $value, ?int $order)
    {
        return $this->unitOfWork->questionOptions()->create([
            'question_id' => $questionId,
            'option_text' => $option,
            'value' => $value,
            'order' => $order ?? 0,
        ]);
    }

    /**
     * Get statistical data for the enrollment dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStats(): array
    {
        try {
            $questionsRepo = $this->unitOfWork->questions();
            $typeRepo = $this->unitOfWork->questionTypes();
            $categoryRepo = $this->unitOfWork->questionCategories();

            return [
                'total_questions' => [
                    'value' => formatNumber($questionsRepo->count()),
                    'updated' => formatDate($questionsRepo->latestUpdateTime()),
                ],
                'total_questions_types' => [
                    'value' => formatNumber($typeRepo->count()),
                    'updated' => formatDate($typeRepo->latestUpdateTime()),
                ],
                'total_questions_categories' => [
                    'value' => formatNumber($categoryRepo->count()),
                    'updated' => formatDate($categoryRepo->latestUpdateTime()),
                ],
            ];
        } catch (Exception $e) {
            logError('Failed to fetch question statistics', 'QuestionService', $e);
            throw new ServiceException('Unable to retrieve question statistics due to system error', 0, $e);
        }
    }

    /**
     * Get the data table for questions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->questions()->query()
                ->with(['type', 'category', 'options']);
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', fn($question) => $question->type ? __($question->type->name) : 'N/A')
                ->addColumn('category', fn($question) => $question->category ? __($question->category->name) : 'N/A')
                ->addColumn('created_at', fn($question) => $question->created_at ? formatDate($question->created_at) : 'N/A')
                ->addColumn('actions', function($question) {
                    return $this->getActionButtons($question);
                })
                ->addColumn('has_options', function($question) {
                    return $question->hasOptions();
                })
                ->addColumn('options', function($question) {
                    return $question->options->map(function($option) {
                        return [
                            'option_text' => $option->option_text,
                            'value' => $option->value,
                            'order' => $option->order,
                        ];
                    })->values();
                })
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to load question data', 'QuestionService', $e);
            throw new ServiceException('Unable to load question data due to system error', 0, $e);
        }
    }

    /**
     * Generate action buttons for a question row in the DataTable.
     *
     * @param $question
     * @return string
     */
    protected function getActionButtons($question)
    {
        if ($question->hasOptions() && $question->options->count() > 0) {
            $optionsJson = htmlspecialchars(json_encode($question->options->map(function($option) {
                return [
                    'option_text' => $option->option_text,
                    'value' => $option->value,
                    'order' => $option->order,
                ];
            })->values()), ENT_QUOTES, 'UTF-8');
            return '<button class="btn btn-outline-primary btn-sm show-options" data-options="'.$optionsJson.'" title="عرض الخيارات"><i class="fa fa-list"></i></button>';
        } else {
            return '<button class="btn btn-outline-secondary btn-sm" disabled title="لا توجد خيارات"><i class="fa fa-list"></i></button>';
        }
    }

    /**
     * Check if a question is an instructor select type
     *
     * @param array $question
     * @return bool
     */
    private function isInstructorSelectQuestion(array $question): bool
    {
        $typeId = $question['type_id'] ?? null;
        if (!$typeId) {
            return false;
        }
        $type = $this->unitOfWork->questionTypes()->find($typeId);
        return $type && $type->name === 'Instructor Select';
    }

    /**
     * Get instructors for a course to be used as question options
     *
     * @param int $courseId
     * @return array
     */
    public function getCourseInstructorsForOptions(int $courseId): array
    {
        try {
            $instructors = SemesterCourseInstructor::whereHas('semesterCourse', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->with(['facultyMember.user'])
            ->get()
            ->map(function($instructor) {
                return [
                    'id' => $instructor->facultyMember->user->id,
                    'text' => $instructor->facultyMember->user->full_name,
                    'is_primary' => $instructor->is_primary
                ];
            })
            ->unique('id')
            ->values()
            ->toArray();

            return $instructors;
        } catch (Exception $e) {
            logError('Error getting course instructors for options', 'QuestionService', $e);
            throw $e;
        }
    }

}
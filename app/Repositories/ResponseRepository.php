<?php

namespace App\Repositories;

use App\Models\Response;
use App\Contracts\ResponseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Models\DeployedQuestionnaire;
use App\Models\Question;
use App\Exceptions\BusinessException;
use App\Models\DeployedQuestion;
use App\Models\Student;
use App\Models\QuestionnaireTarget;
use Exception;

class ResponseRepository extends BaseRepository implements ResponseRepositoryInterface
{

    /**
     * Get the model class name.
     *
     * @return string
     */
    public function model(): string
    {
        return Response::class;
    }

    /**
     * Get weekly response data.
     *
     * @return array
     */
    public function getWeeklyData(): array
    {
        try {
            $weekly = $this->model
                ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                ->where('created_at', '>=', now()->subWeek())
                ->groupBy('day')
                ->get()
                ->mapWithKeys(function ($item) {
                    $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                    return [$days[$item->day - 1] => $item->count];
                })->toArray();

            return [
                'labels' => array_keys($weekly),
                'data' => array_values($weekly),
            ];
        } catch (Exception $e) {
            LogError('Failed to get weekly response data', 'ResponseRepository', $e);
            throw $e;
        }
    }

    public function getMonthlyData(): array
    {
        try {
            $monthly = $this->model
                ->selectRaw('WEEK(created_at) as week, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonth())
                ->groupBy('week')
                ->get()
                ->mapWithKeys(function ($item, $key) {
                    return ['الأسبوع ' . ($key + 1) => $item->count];
                })->toArray();

            return [
                'labels' => array_keys($monthly),
                'data' => array_values($monthly),
            ];
        } catch (Exception $e) {
            LogError('Failed to get monthly response data', 'ResponseRepository', $e);
            throw $e;
        }
    }

    public function getYearlyData(): array
    {
        try {
            $yearly = $this->model
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->where('created_at', '>=', now()->subYear())
                ->groupBy('month')
                ->get()
                ->mapWithKeys(function ($item) {
                    $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                    return [$months[$item->month - 1] => $item->count];
                })->toArray();

            return [
                'labels' => array_keys($yearly),
                'data' => array_values($yearly),
            ];
        } catch (Exception $e) {
            LogError('Failed to get yearly response data', 'ResponseRepository', $e);
            throw $e;
        }
    }

    /**
     * Create a questionnaire response.
     * @param array $response The response data
     * @return Response The created response
     * @throws BusinessException If validation or creation fails
     */
    public function createResponse($response): Response
    {
        try {
            $questionnaireId = $response['questionnaire_id'];
            $respondentId = auth()->check() ? auth()->user()->id : null;
            $anonymousToken = $response['anonymous_token'] ?? null;
            $questionResponses = $response['responses'];
            $timeTaken = $response['time_taken'] ?? null;

            // Check eligibility
            $this->checkEligibility($questionnaireId, $respondentId, $anonymousToken);

            // Create response and question responses in a transaction
            return DB::transaction(function () use ($questionnaireId, $respondentId, $anonymousToken, $questionResponses, $timeTaken) {
                $modelResponse = $this->model->create([
                    'questionnaire_id' => $questionnaireId,
                    'user_id' => $respondentId,
                    'anonymous_token' => $anonymousToken,
                    'submitted_at' => Carbon::now(),
                    'time_taken' => $timeTaken,
                ]);

                $this->storeQuestionResponses($modelResponse, $questionResponses);

                return $modelResponse;
            });
        } catch (Exception $e) {
            LogError('Failed to create questionnaire response', 'ResponseRepository', $e);
            throw $e;
        }
    }

    /**
     * Check if the response is eligible.
     * @param int $questionnaireId The questionnaire ID
     * @param int|null $respondentId The respondent ID
     * @param string|null $anonymousToken The anonymous token
     * @throws BusinessException If ineligible
     */
    private function checkEligibility(int $questionnaireId, ?int $respondentId, ?string $anonymousToken): void
    {
        // Validate questionnaire
        if (!$this->isQuestionnaireValid($questionnaireId)) {
            throw new BusinessException('Questionnaire is invalid or closed');
        }

        if (!$this->notSubmittedBefore($questionnaireId, $respondentId, $anonymousToken)) {
            throw new BusinessException('You have already submitted a response for this questionnaire');
        }
    }

    /**
     * Check if the questionnaire is valid and open.
     * @param int $questionnaireId The questionnaire ID
     * @return bool
     */
    private function isQuestionnaireValid(int $questionnaireId): bool
    {
        try {
            $isQuestionnaireValid = DeployedQuestionnaire::where('id', $questionnaireId)
                ->where('status', 'active')
                ->where('close_date', '>=', Carbon::now())
                ->exists();
            return $isQuestionnaireValid;
        } catch (Exception $e) {
            LogError('Failed to check if questionnaire is valid', 'ResponseRepository', $e);
            throw $e;
        }
    }

    /**
     * Check if the user has not submitted before.
     * @param int $questionnaireId The questionnaire ID
     * @param int|null $respondentId The respondent ID
     * @param string|null $anonymousToken The anonymous token
     * @return bool
     */
    private function notSubmittedBefore(int $questionnaireId, ?int $respondentId, ?string $anonymousToken): bool
    {
        try {
            $query = $this->model->where('questionnaire_id', $questionnaireId);

            if ($respondentId) {
                $query->where('user_id', $respondentId);
            } elseif ($anonymousToken) {
                $query->where('anonymous_token', $anonymousToken);
            }

            return !$query->exists();
        } catch (Exception $e) {
            LogError('Failed to check if user has not submitted before', 'ResponseRepository', $e);
            throw $e;
        }
    }

    /**
     * Store question responses for a response.
     * @param Response $response The parent response
     * @param array $questionResponses Array of question responses
     * @throws BusinessException If validation fails
     */
    private function storeQuestionResponses(Response $response, array $questionResponses): void
    {
        try {
            foreach ($questionResponses as $questionId => $responseData) {
                if (!is_array($responseData)) {
                    throw new BusinessException('Invalid response format for question');
                }

                $data = [
                    'question_id' => $questionId,
                    'text_response' => isset($responseData['text_response']) ? 
                        htmlspecialchars($responseData['text_response'], ENT_QUOTES, 'UTF-8') : null,
                    'option_id' => $responseData['option_id'] ?? null,
                    'numeric_value' => $responseData['numeric_value'] ?? null
                ];

               if (
                    $responseData['type'] != '3' &&
                    is_null($data['text_response']) &&
                    is_null($data['option_id']) &&
                    is_null($data['numeric_value'])
                ) {
                    throw new BusinessException('No response provided for question');
                }


                $response->questionResponses()->create($data);
            }
        } catch (Exception $e) {
            LogError('Failed to store question responses', 'ResponseRepository', $e);
            throw $e;
        }
    }

    /**
     * Get response data grouped by timeframe (weekly, monthly, yearly)
     * @return array The formatted response data
     */
    public function getResponseDataByTimeframe(): array
    {
        $weekly = $this->model
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->where('created_at', '>=', now()->subWeek())
            ->distinct()
            ->groupBy('day')
            ->get()
            ->mapWithKeys(function ($item) {
                $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                return [$days[$item->day - 1] => $item->count];
            })->toArray();

        $monthly = $this->model
            ->selectRaw('WEEK(created_at) as week, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonth())
            ->groupBy('week')
            ->get()
            ->mapWithKeys(function ($item, $key) {
                return ['الأسبوع ' . ($key + 1) => $item->count];
            })->toArray();

        $yearly = $this->model
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                return [$months[$item->month - 1] => $item->count];
            })->toArray();

        return [
            'weekly' => [
                'labels' => array_keys($weekly),
                'data' => array_values($weekly),
            ],
            'monthly' => [
                'labels' => array_keys($monthly),
                'data' => array_values($monthly),
            ],
            'yearly' => [
                'labels' => array_keys($yearly),
                'data' => array_values($yearly),
            ],
        ];
    }

    public function getResponsesQuestionsByQuestionnaire(int $questionnaireId): array
    {
        try {
            $questions = $this->getDeployedQuestions($questionnaireId);
            $responses = $this->getQuestionnaireResponses($questionnaireId);
            
            $formattedQuestions = $this->formatQuestions($questions, $responses);
            $overallStats = $this->calculateOverallStats($questions, $responses);
            $categoryStats = $this->calculateCategoryStats($questions, $responses);

            return [
                'questions' => $formattedQuestions,
                'overall_stats' => $overallStats,
                'category_stats' => array_values($categoryStats),
            ];
        } catch (\Exception $e) {
            throw new BusinessException(
                'Failed to analyze questionnaire responses: ' . $e->getMessage()
            );
        }
    }

    private function getDeployedQuestions($questionnaireId)
    {
        return DeployedQuestion::with([
            'options',
            'question',
            'question.type',
            'question.category',
        ])
            ->where('questionnaire_id', $questionnaireId)
            ->orderBy('order')
            ->get();
    }

    private function getQuestionnaireResponses($questionnaireId)
    {
        return $this->model
            ->with(['questionResponses.question', 'questionResponses.option', 'questionnaire'])
            ->where('questionnaire_id', $questionnaireId)
            ->get();
    }

    private function formatQuestions($questions, $responses)
    {
        $formattedQuestions = [];
        
        foreach ($questions as $question) {
            $questionData = $this->buildBaseQuestionData($question);
            $allResponses = $this->getQuestionResponses($responses, $question->id);
            
            if ($question->options->isNotEmpty()) {
                $questionData = $this->processOptionBasedQuestion($questionData, $question, $allResponses);
            } else {
                $questionData = $this->processTextBasedQuestion($questionData, $allResponses);
            }
            
            $formattedQuestions[] = $questionData;
        }
        
        return $formattedQuestions;
    }

    private function buildBaseQuestionData($question)
    {
        return [
            'id' => $question->id,
            'text' => $question->getText(),
            'type' => $question->question->type,
            'options' => [],
            'top_choice' => null,
            'average' => null,
        ];
    }

    private function getQuestionResponses($responses, $questionId)
    {
        return $responses->flatMap->questionResponses
            ->where('question_id', $questionId);
    }

    private function processOptionBasedQuestion($questionData, $question, $allResponses)
    {
        $questionData['options'] = $this->buildOptionsArray($question, $allResponses);
        
        if ($this->isScaleType($question->question->type->name)) {
            $questionData = $this->calculateScaleAverage($questionData, $question, $allResponses);
        }
        
        if ($this->isChoiceType($question->question->type->name)) {
            $questionData['top_choice'] = $this->calculateTopChoice($question, $allResponses);
        }
        
        return $questionData;
    }

    private function processTextBasedQuestion($questionData, $allResponses)
    {
        $textResponses = $allResponses
            ->map(function($qr) {
                return [
                    'text' => $qr->text_response,
                    'time' => $qr->created_at
                ];
            })
            ->filter(function($item) {
                return !empty($item['text']);
            })
            ->values();
        
        $questionData['responses'] = $textResponses->toArray();
        $questionData['count'] = $textResponses->count();
        
        return $questionData;
    }

    private function buildOptionsArray($question, $allResponses)
    {
        $options = [];
        
        foreach ($question->options as $option) {
            $selectedCount = $allResponses
                ->where('option_id', $option->id)
                ->count();
            
            $options[] = [
                'id' => $option->id,
                'text' => $option->option_text,
                'count' => $selectedCount,
                'value' => $option->value ?? null
            ];
        }
        
        return $options;
    }

    private function calculateScaleAverage($questionData, $question, $allResponses)
    {
        $totalValue = 0;
        $totalResponses = 0;
        
        foreach ($question->options as $option) {
            $selectedCount = $allResponses
                ->where('option_id', $option->id)
                ->count();
            
            $optionValue = $option->value ?? 0;
            $totalValue += $selectedCount * $optionValue;
            $totalResponses += $selectedCount;
        }
        
        if ($totalResponses > 0) {
            $questionData['average'] = round($totalValue / $totalResponses, 2);
        }
        
        return $questionData;
    }

    private function calculateTopChoice($question, $allResponses)
    {
        $responsesByOption = $allResponses->groupBy('option_id')
            ->map(function($group) {
                return $group->count();
            });

        if ($responsesByOption->isEmpty()) {
            return null;
        }

        $maxCount = $responsesByOption->max();
        $topOptionId = $responsesByOption->search($maxCount);
        $totalResponses = $responsesByOption->sum();
        $percentage = round(($maxCount / $totalResponses) * 100, 2);
        
        return [
            'option_id' => $topOptionId,
            'option_text' => $question->options->where('id', $topOptionId)->first()->option_text ?? null,
            'count' => $maxCount,
            'percentage' => $percentage
        ];
    }

    private function calculateOverallStats($questions, $responses)
    {
        $overallStats = $this->initializeOverallStats();
        $allLikertResponses = collect();
        $allRatingResponses = collect();
        
        foreach ($questions as $question) {
            if ($question->options->isEmpty()) {
                continue;
            }
            
            $allResponses = $this->getQuestionResponses($responses, $question->id);
            $typeName = $question->question->type->name;
            
            if ($this->isScaleType($typeName)) {
                $this->processScaleForOverallStats($question, $allResponses, $overallStats, $allLikertResponses, $allRatingResponses);
            }
        }
        
        $this->finalizeOverallStats($overallStats, $allLikertResponses, $allRatingResponses);
        
        return $overallStats;
    }

    private function initializeOverallStats()
    {
        return [
            'likert_average' => 0,
            'rating_average' => 0,
            'total_likert_responses' => 0,
            'total_rating_responses' => 0,
            'top_likert_choice' => null,
            'top_rating_choice' => null
        ];
    }

    private function processScaleForOverallStats($question, $allResponses, &$overallStats, &$allLikertResponses, &$allRatingResponses)
    {
        $totalValue = 0;
        $totalResponses = 0;
        $typeName = $question->question->type->name;
        
        foreach ($question->options as $option) {
            $selectedCount = $allResponses
                ->where('option_id', $option->id)
                ->count();
            
            $optionValue = $option->value ?? 0;
            $totalValue += $selectedCount * $optionValue;
            $totalResponses += $selectedCount;
            
            $responseData = [
                'option_id' => $option->id,
                'option_text' => $option->option_text,
                'count' => $selectedCount
            ];
            
            if ($typeName === 'Likert Scale') {
                $allLikertResponses->push($responseData);
            } else {
                $allRatingResponses->push($responseData);
            }
        }
        
        if ($totalResponses > 0) {
            if ($typeName === 'Likert Scale') {
                $overallStats['likert_average'] += $totalValue;
                $overallStats['total_likert_responses'] += $totalResponses;
            } else {
                $overallStats['rating_average'] += $totalValue;
                $overallStats['total_rating_responses'] += $totalResponses;
            }
        }
    }

    private function finalizeOverallStats(&$overallStats, $allLikertResponses, $allRatingResponses)
    {
        if ($overallStats['total_likert_responses'] > 0) {
            $overallStats['likert_average'] = round(
                $overallStats['likert_average'] / $overallStats['total_likert_responses'],
                2
            );
            $overallStats['top_likert_choice'] = $this->calculateTopChoiceFromResponses($allLikertResponses);
        }
        
        if ($overallStats['total_rating_responses'] > 0) {
            $overallStats['rating_average'] = round(
                $overallStats['rating_average'] / $overallStats['total_rating_responses'],
                2
            );
            $overallStats['top_rating_choice'] = $this->calculateTopChoiceFromResponses($allRatingResponses);
        }

        // Remove the total response counts from the final output
        unset($overallStats['total_likert_responses']);
        unset($overallStats['total_rating_responses']);
    }

    private function calculateTopChoiceFromResponses($responses)
    {
        if ($responses->isEmpty()) {
            return null;
        }
        
        $groupedResponses = $responses->groupBy('option_text')
            ->map(function($group) {
                return $group->sum('count');
            });
        
        $maxCount = $groupedResponses->max();
        $topText = $groupedResponses->search($maxCount);
        $totalResponses = $groupedResponses->sum();
        
        return [
            'option_text' => $topText,
            'count' => $maxCount,
            'percentage' => round(($maxCount / $totalResponses) * 100, 2)
        ];
    }

    private function calculateCategoryStats($questions, $responses)
    {
        $categoryStats = [];
        
        foreach ($questions as $question) {
            if (!$question->question || !$question->question->category) {
                continue;
            }
            
            $categoryId = $question->question->category->id;
            $categoryName = $question->question->category->name;
            
            if (!isset($categoryStats[$categoryId])) {
                $categoryStats[$categoryId] = $this->initializeCategoryStats($categoryName);
            }
            
            $categoryStats[$categoryId]['question_count']++;
            $this->processCategoryQuestion($question, $responses, $categoryStats[$categoryId]);
        }
        
        $this->finalizeCategoryStats($categoryStats);
        
        return $categoryStats;
    }

    private function initializeCategoryStats($categoryName)
    {
        return [
            'name' => $categoryName,
            'likert_average' => 0,
            'rating_average' => 0,
            'total_likert_responses' => 0,
            'total_rating_responses' => 0,
            'top_likert_choice' => null,
            'top_rating_choice' => null,
            'question_count' => 0,
            'likert_responses' => collect(),
            'rating_responses' => collect()
        ];
    }

    private function processCategoryQuestion($question, $responses, &$categoryStats)
    {
        if ($question->options->isEmpty()) {
            return;
        }
        
        $allResponses = $this->getQuestionResponses($responses, $question->id);
        $typeName = $question->question->type->name;
        
        if (!$this->isScaleType($typeName)) {
            return;
        }
        
        $totalValue = 0;
        $totalResponses = 0;
        
        foreach ($question->options as $option) {
            $selectedCount = $allResponses
                ->where('option_id', $option->id)
                ->count();
            
            $optionValue = $option->value ?? 0;
            $totalValue += $selectedCount * $optionValue;
            $totalResponses += $selectedCount;
            
            $responseData = [
                'option_id' => $option->id,
                'option_text' => $option->option_text,
                'count' => $selectedCount
            ];
            
            if ($typeName === 'Likert Scale') {
                $categoryStats['likert_responses']->push($responseData);
            } else if ($typeName === 'Rating') {
                $categoryStats['rating_responses']->push($responseData);
            }
        }
        
        if ($totalResponses > 0) {
            if ($typeName === 'Likert Scale') {
                $categoryStats['likert_average'] += $totalValue;
                $categoryStats['total_likert_responses'] += $totalResponses;
            } else {
                $categoryStats['rating_average'] += $totalValue;
                $categoryStats['total_rating_responses'] += $totalResponses;
            }
        }
    }

    private function finalizeCategoryStats(&$categoryStats)
    {
        foreach ($categoryStats as &$stats) {
            if ($stats['total_likert_responses'] > 0) {
                $stats['likert_average'] = round(
                    $stats['likert_average'] / $stats['total_likert_responses'],
                    2
                );
                $stats['top_likert_choice'] = $this->calculateTopChoiceFromResponses($stats['likert_responses']);
            }
            
            if ($stats['total_rating_responses'] > 0) {
                $stats['rating_average'] = round(
                    $stats['rating_average'] / $stats['total_rating_responses'],
                    2
                );
                $stats['top_rating_choice'] = $this->calculateTopChoiceFromResponses($stats['rating_responses']);
            }
            
            // Clean up temporary collections
            unset($stats['likert_responses'], $stats['rating_responses']);
        }
    }

    private function isScaleType($typeName)
    {
        return in_array($typeName, ['Rating', 'Likert Scale']);
    }

    private function isChoiceType($typeName)
    {
        return in_array($typeName, ['Likert Scale', 'Rating', 'Multiple Choice', 'Single Choice']);
    }

    /**
     * Delete responses for a student in a specific semester course. - handle if it was one only from targets
     *
     * @param int $studentId
     * @param int $semesterCourseId
     * @return bool
     */
    public function deleteResponsesByStudentAndSemesterCourse(int $studentId, int $semesterCourseId): bool
    {
        try {
            // Get the student's user ID
            $student = Student::findOrFail($studentId);
            $userId = $student->user_id;

            // Get all questionnaires targeting this semester course
            $questionnaireIds = QuestionnaireTarget::where('semester_course_id', $semesterCourseId)
                ->pluck('questionnaire_id');

            // Get all responses for this user in these questionnaires
            $responses = $this->model->where('user_id', $userId)
                ->whereIn('questionnaire_id', $questionnaireIds)
                ->get();

            // Delete each response using the BaseRepository delete method
            foreach ($responses as $response) {
                $this->delete($response->id);
            }

            return true;
                
        } catch (Exception $e) {
            LogError('Failed to delete responses', 'ResponseRepository', $e);
            throw $e;
        }
    }
}
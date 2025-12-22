<?php

namespace App\Services\Business;


use App\Services\BaseService;
use App\Contracts\UnitOfWorkInterface;


class CourseQuestionnaireSummaryService extends BaseService
{
    protected $questionnaire;

    public function __construct(UnitOfWorkInterface $unitOfWork, $questionnaire)
    {
        parent::__construct($unitOfWork);
        $this->questionnaire = $questionnaire;
    }
    
    private function getQualityQuestions()
    {
        $questions = $this->unitOfWork->deployedQuestions()->qualityBasedLikertScaleQuestionsPerQuestionnaire($this->questionnaire->id);
        return $questions;
    }
    
    private function getOptions($questionId)
    {
        $options = $this->unitOfWork->deployedQuestions()->questionOptions($questionId);
        return $options;
    }

    private function getResponses($questionnaireId, $questionId)
    {
        $responses = $this->unitOfWork->questionResponses()->getByQuestionnaireAndQuestion($questionnaireId, $questionId);
        return $responses;
    }

    private function calcStats($question, $options, $responses)
    {
        $stats = $this->initializeStats($options);
        $this->countResponses($stats, $responses);
        $this->calculatePercentages($stats, count($responses));
        $average = $this->calculateAverage($stats, count($responses));
        return [
            'stats' => $stats,
            'average' => $average
        ];
    }

    private function initializeStats($options)
    {
        $stats = [];
        
        foreach ($options as $option) {
            $stats[$option->id] = [
                'option' => $option,
                'count' => 0,
                'percentage' => 0.0
            ];
        }

        return $stats;
    }

    private function countResponses(array &$stats, $responses)
    {
        foreach ($responses as $response) {
            $optionId = $this->getResponseOptionId($response);
            
            if ($optionId && isset($stats[$optionId])) {
                $stats[$optionId]['count']++;
            }
        }
    }

    private function getResponseOptionId($response)
    {
        return $response->option->id ?? $response->option_id ?? null;
    }

    private function calculatePercentages(array &$stats, int $totalResponses)
    {
        if ($totalResponses === 0) {
            return;
        }

        foreach ($stats as &$stat) {
            $stat['percentage'] = round(($stat['count'] / $totalResponses) * 100, 2);
        }
    }

    private function calculateAverage(array $stats, int $totalResponses)
    {
        if ($totalResponses === 0) {
            return 0;
        }
        $sum = 0;
        foreach ($stats as $stat) {
            $optionValue = $stat['option']->value ?? null;
            if ($optionValue !== null) {
                $sum += $optionValue * $stat['count'];
            }
        }
        return round($sum / $totalResponses, 2);
    }

    public function generateSummary()
    {
        $summary = [];
        $questions = $this->getQualityQuestions();
        foreach ($questions as $question) {
            $options = $this->getOptions($question->id);
            $responses = $this->getResponses($this->questionnaire->id, $question->id);
            $statsResult = $this->calcStats($question, $options, $responses);
            $summary[$question->id] = [
                'question' => $question,
                'stats' => $statsResult['stats'],
                'average' => $statsResult['average'],
                'total_responses' => count($responses)
            ];
        }
        return $summary;
    }
}
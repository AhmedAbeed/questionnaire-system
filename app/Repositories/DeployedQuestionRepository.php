<?php

namespace App\Repositories;

use App\Models\DeployedQuestion;
use App\Contracts\DeployedQuestionRepositoryInterface;

class DeployedQuestionRepository extends BaseRepository implements DeployedQuestionRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return DeployedQuestion::class;
    }

    public function qualityBasedLikertScaleQuestionsPerQuestionnaire($questionnaireId)
    {
        try {
            return $this->model
                ->whereHas('question.type', function ($query) {
                    $query->where('name', 'Likert Scale');
                })
                ->where('questionnaire_id', $questionnaireId)
                ->get();
        } catch (Exception $e) {
            logError('Failed to get Likert scale questions', $this->getRepositoryContext(), $e);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function questionOptions($questionId)
    {
        try {
            $question = $this->model->findOrFail($questionId);
            return $question->options;
        } catch (Exception $e) {
            logError('Failed to get question options', $this->getRepositoryContext(), $e);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
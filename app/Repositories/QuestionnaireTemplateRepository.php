<?php

namespace App\Repositories;

use App\Models\QuestionnaireTemplate;
use App\Contracts\QuestionnaireTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuestionnaireTemplateRepository extends BaseRepository implements QuestionnaireTemplateRepositoryInterface
{
    /**
     * Get the model class name.
     *
     * @return string
     */
    public function model(): string
    {
        return QuestionnaireTemplate::class;
    }

    /**
     * Get active templates
     *
     * @return Collection
     */
    public function getActiveQuestionnaireTemplates(): Collection
    {
        try {
            $templates = $this->model->where('is_active', 1)->get();
            return $templates;
        } catch (Exception $e) {
            logError('Failed to get active questionnaire templates', 'QuestionnaireTemplateRepository', $e);
            throw $e;
        }
    }

    /**
     * Get template with questions
     *
     * @param int $templateId
     * @return QuestionnaireTemplate|null
     */
    /**
     * Get template with questions
     *
     * @param int $templateId
     * @return QuestionnaireTemplate|null
     */
    public function getQuestionnaireTemplate(int $templateId): ?QuestionnaireTemplate
    {
        try {
            return $this->model->with([
                'templateQuestions' => function($query) {
                    $query->orderBy('order');
                },
                'templateQuestions.question.type',
                'templateQuestions.question.options',
                'deployedQuestionnaires',
            ])->find($templateId);
        } catch (Exception $e) {
            logError('Failed to get questionnaire template', 'QuestionnaireTemplateRepository', $e);
            throw $e;
        }
    }

    
}
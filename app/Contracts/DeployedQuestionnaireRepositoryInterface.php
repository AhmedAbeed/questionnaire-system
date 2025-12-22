<?php

namespace App\Contracts;

use App\Models\DeployedQuestionnaire;
use Illuminate\Database\Eloquent\Collection;

interface DeployedQuestionnaireRepositoryInterface extends RepositoryInterface
{
    /**
     * Get active questionnaires with formatted data
     * 
     * @return Collection The collection of active questionnaires
     */
    public function getActiveQuestionnaires(): Collection;

    /**
     * Get questionnaires eligible for the authenticated respondent
     * 
     * @return Collection The collection of eligible questionnaires
     */
    public function getRespondentEligibleQuestionnaires(): Collection;

    /**
     * Get questionnaire statistics by ID
     * 
     * @param int $deployedQuestionnaireId The questionnaire ID
     * @return DeployedQuestionnaire|null The questionnaire with stats or null if not found
     */
    public function getQuestionnaireStatsById(int $deployedQuestionnaireId): ?DeployedQuestionnaire;
}

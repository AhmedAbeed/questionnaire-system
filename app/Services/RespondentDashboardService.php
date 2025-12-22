<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class RespondentDashboardService extends BaseService
{
    /**
     * Fetch dashboard data for the respondent.
     *
     * @return array<string, mixed> Dashboard data including eligible questionnaires.
     * @throws Exception
     */
    public function getDashboardData(): array
    {
        try {
            return [
                'eligible_questionnaires' => $this->getEligibleQuestionnaires(),
            ];
        } catch (BusinessValidationException $e) {
            logError('Failed to fetch respondent dashboard data', 'RespondentDashboardService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to fetch respondent dashboard data', 'RespondentDashboardService', $e);
            throw new ServiceException('Unable to fetch respondent dashboard data due to system error', 0, $e);
        }
    }

    /**
     * Get eligible questionnaires for the respondent.
     *
     * @return mixed
     */
    public function getEligibleQuestionnaires()
    {
        return $this->unitOfWork->questionnaires()->getRespondentEligibleQuestionnaires();
    }

    /**
     * Get a specific deployed questionnaire by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function getDeployedQuestionnaire($id)
    {
        return $this->unitOfWork->questionnaires()->find($id);
    }

    /**
     * Answer a deployed questionnaire.
     *
     * @param int $id
     * @return mixed
     */
    public function answerDeployedQuestionnaire($id)
    {
        return $this->unitOfWork->questionnaires()->answerDeployedQuestionnaire($id);
    }

    /**
     * Get default dashboard data in case of an error.
     *
     * @return array<string, mixed> Default (empty) dashboard data structure.
     */
    private function getDefaultDashboardData(): array
    {
        return [
            'eligible_questionnaires' => [],
        ];
    }
}
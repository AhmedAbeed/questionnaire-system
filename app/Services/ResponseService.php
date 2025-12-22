<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use App\Events\QuestionnaireSubmitted;
use App\Models\QuestionnaireAnalysis;
use App\Exceptions\BusinessException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class ResponseService extends BaseService
{
    /**
     * Create a questionnaire response and fire event
     *
     * @param array $data
     * @return mixed
     * @throws BusinessException|Exception
     */
    public function createResponse(array $data)
    {
        try {
            $response = $this->unitOfWork->responses()->createResponse($data);
            event(new QuestionnaireSubmitted(
                $response->questionnaire,
                $response,
                auth()->user()
            ));
            return $response;
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to create response', 'ResponseService', $e);
            throw new ServiceException('Unable to create response due to system error', 0, $e);
        }
    }

    /**
     * Get responses and AI insights for a questionnaire
     *
     * @param int $QuestionnaireId
     * @return array
     * @throws BusinessException|Exception
     */
    public function getResponsesQuestionsByQuestionnaire($QuestionnaireId)
    {
        try {
            $Questionnaire = $this->unitOfWork->questionnaires()->getQuestionnaireStatsById($QuestionnaireId);
            $responses = $this->unitOfWork->responses()->getResponsesQuestionsByQuestionnaire($QuestionnaireId);
            
            // Get AI analysis from database
            $aiAnalysis = QuestionnaireAnalysis::where('questionnaire_id', $QuestionnaireId)
                ->where('status', 'success')
                ->latest('generated_at')
                ->first();
            
            $aiInsights = $aiAnalysis ? $aiAnalysis->analysis_data : null;
        
            $stats = [
                'Questionnaire' => $Questionnaire,
                'responses' => $responses,
                'aiInsights' => $aiInsights
            ];
            return $stats;
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to fetch responses by questionnaire', 'ResponseService', $e);
            throw new ServiceException('Unable to fetch responses by questionnaire due to system error', 0, $e);
        }
    }
}
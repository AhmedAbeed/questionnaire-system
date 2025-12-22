<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Exceptions\BusinessException;
use App\Services\ResponseService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class QualityManagerDashboardService extends BaseService
{
    /**
     * Get active questionnaires for the dashboard.
     *
     * @return array<int, mixed> Array of active questionnaire data.
     * @throws Exception
     */
    private function getActiveQuestionnaires()
    {
        try {
            $activeQs = $this->processActiveQuestionnaire($this->unitOfWork->questionnaires()->getActiveQuestionnaires());
            return $activeQs;
        } catch (Exception $e) {
            logError('Failed to retrieve active questionnaires', 'QualityManagerDashboardService', $e);
            throw $e;
        }
    }

     public function processActiveQuestionnaire($activeQs){
        return $activeQs->map(function ($questionnaire) {
            $target = $questionnaire->targets->first();
            return [
                'title' => $questionnaire->name,
                'start' => $questionnaire->open_date->format('d M'),
                'end' => $questionnaire->close_date->format('d M'),
                'completion' => $questionnaire->getCompletionRateAttribute(),
                'course' => $target?->semesterCourse?->course?->name ?? 'N/A',
                'faculty' => $target?->semesterCourse?->course?->faculty?->name ?? 'N/A',
                'responses' => $questionnaire->questionnaireResponseCount(),
                'students' => $questionnaire->getEligibleRespondentsCount(),
                'daysAgo' => $questionnaire->close_date->diffInDays(now()),
            ];
        });
    }

    /**
     * Get statistical data for the dashboard.
     *
     * @return array<string, array<string, string>> Array of stat cards containing count and timestamp data
     * @throws Exception When there's an error retrieving stats
     */
    public function getStats(): array
    {
        try {
            $latestStudentsUpdate = $this->unitOfWork->students()->latestUpdateTime();
            $latestQuestionnairesUpdate = $this->unitOfWork->questionnaires()->latestUpdateTime();
            $latestResponsesUpdate = $this->unitOfWork->responses()->latestUpdateTime();

            return [
                'total_students' => [
                    'value' => formatNumber($this->unitOfWork->students()->count(), 0),
                    'updated' => formatDate($latestStudentsUpdate ? Carbon::parse($latestStudentsUpdate) : null),
                ],
                'active_questionnaires' => [
                    'value' => formatNumber($this->unitOfWork->questionnaires()->getActiveQuestionnaires()->count(), 0),
                    'updated' => formatDate($latestQuestionnairesUpdate ? Carbon::parse($latestQuestionnairesUpdate) : null),
                ],
                'total_responses' => [
                    'value' => formatNumber($this->unitOfWork->responses()->count(), 0),
                    'updated' => formatDate($latestResponsesUpdate ? Carbon::parse($latestResponsesUpdate) : null),
                ],
            ];
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve stats', 'QualityManagerDashboardService', $e);
            throw new ServiceException('Unable to retrieve quality manager dashboard statistics due to system error', 0, $e);
        }
    }


    /**
     * Get chart data for response visualization.
     *
     * @return array<string, mixed> Chart data for weekly, monthly, and yearly responses.
     * @throws Exception
     */
    public function getChartData(): array
    {
        try {
            return $this->unitOfWork->responses()->getResponseDataByTimeframe();
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve chart data', 'QualityManagerDashboardService', $e);
            throw new ServiceException('Unable to retrieve chart data due to system error', 0, $e);
        }
    }

    /**
     * Get engagement data for a specified timeframe.
     *
     * @param string $timeframe Timeframe for data ('daily', 'weekly', 'monthly')
     * @return array<string, mixed>
     * @throws Exception
     */
    public function getResponseDataByTimeframe(): array
    {
        return [
            'weekly' => $this->unitOfWork->responses()->getWeeklyData(),
            'monthly' => $this->unitOfWork->responses()->getMonthlyData(),
            'yearly' => $this->unitOfWork->responses()->getYearlyData(),
        ];
    }
}
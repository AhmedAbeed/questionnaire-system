<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use App\Exceptions\ServiceException;
use App\Services\ResponseService;

class AdminDashboardService extends BaseService
{
    /**
     * Fetch dashboard data for the superadmin.
     *
     * @return array<string, mixed> Dashboard data including stats, questionnaires, question bank, and chart data.
     * @throws Exception
     */
    public function getDashboardData(): array
    {
        try {
            return [
                'questionBank' => $this->getQuestionBankData(),
            ];

        } catch (Exception $e) {
            logError('Failed to retrieve dashboard data', 'AdminDashboardService', $e);
            throw new ServiceException('Unable to retrieve dashboard data due to system error', 0, $e);
        }
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
            $studentsRepo = $this->unitOfWork->students();
            $questionnairesRepo = $this->unitOfWork->questionnaires();
            $responsesRepo = $this->unitOfWork->responses();

            $stats = [
                'total_students' => [
                    'value'   => formatNumber($studentsRepo->count(), 0),
                    'updated' => formatDate($studentsRepo->latestUpdateTime()),
                ],
                'active_questionnaires' => [
                    'value'   => formatNumber($questionnairesRepo->getActiveQuestionnaires()->count(), 0),
                    'updated' => formatDate($questionnairesRepo->latestUpdateTime()),
                ],
                'total_responses' => [
                    'value'   => formatNumber($responsesRepo->count(), 0),
                    'updated' => formatDate($responsesRepo->latestUpdateTime()),
                ],
            ];

            return $stats;
        } catch (Exception $e) {
            logError('Failed to retrieve stats', 'AdminDashboardService', $e);
            throw new ServiceException('Unable to retrieve dashboard stats due to system error', 0, $e);
        }
    }

    /**
     * Get question bank data for the dashboard.
     *
     * @return array<string, mixed> Question bank data including total questions, types, and categories.
     * @throws Exception
     */
    private function getQuestionBankData(): array
    {
        try {
            $questionsRepo = $this->unitOfWork->questions();

            return [
                'totalQuestions' => formatNumber($questionsRepo->count(), 0),
                'questionTypes'  => $this->prepareQuestionTypeStats($questionsRepo->getQuestionsByTypes()),
                'categories'     => $this->prepareQuestionCategoryStats($questionsRepo->groupQuestionsByCategories()),
            ];
        } catch (Exception $e) {
            logError('Failed to retrieve question bank data', 'AdminDashboardService', $e);
            throw $e;
        }
    }

    public function prepareQuestionTypeStats($questionTypes): array
    {
        $total = $questionTypes->sum('count') ?? 0;
    
        return $questionTypes->map(function ($item) use ($total) {
            return [
                'name' => $item->type,
                'icon' => 'bi bi-question-circle',
                'count' => $item->count,
                'percentage' => $total > 0 ? round(($item->count / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Prepare formatted stats from grouped categories.
     *
     * @param \Illuminate\Support\Collection<string, \Illuminate\Support\Collection> $grouped
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function prepareQuestionCategoryStats($questionCategories) :array
    {
        $total = $questionCategories->flatten(1)->count();

        return $questionCategories->map(function ($group, $name) use ($total) {
            $count = $group->count();

            return [
                'name' => $name,
                'icon' => 'bi bi-tag-fill',
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
                'color' => 'secondary',
            ];
        })->toArray();
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
        } catch (Exception $e) {
            logError('Failed to retrieve chart data', 'AdminDashboardService', $e);
            throw new ServiceException('Unable to retrieve chart data due to system error', 0, $e);
        }
    }

}
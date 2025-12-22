<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;
use App\Exceptions\PermissionDeniedException;

class AdminDashboardController extends Controller
{

    public function __construct
    (
        Public AdminDashboardService $dashboardService
    )
    {}

    /**
     * Display the admin dashboard.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->checkPermission('dashboard.admin.view');
            $data = $this->dashboardService->getDashboardData();
            $viewData = [
                'questionBankStats' => [
                    'totalQuestions' => $data['questionBank']['totalQuestions'] ?? 0,
                    'questionTypes' => $data['questionBank']['questionTypes'] ?? [],
                    'categories' => $data['questionBank']['categories'] ?? [],
                ],
            ];
            return view('dashboard.admin', $viewData);
        } catch (PermissionDeniedException $e) {
            abort(403, $e->getMessage());
        } catch (Exception $e) {
            abort(500, __('An error occurred while loading the data. Please try again.'));
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $this->checkPermission('dashboard.admin.stats');
            $stats = $this->dashboardService->getStats();
            return successResponse(__('Statistics retrieved successfully'), data: $stats);
        } catch (PermissionDeniedException $e) {
            return errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return errorResponse(__('An error occurred while loading the data. Please try again.'), 500);
        }
    }

    /**
     * Get dashboard chart data.
     */
    public function getChartData(): JsonResponse
    {
        try {
            $this->checkPermission('dashboard.admin.chart');
            $chartData = $this->dashboardService->getChartData();
            return successResponse(__('Chart data retrieved successfully'),$chartData);
        } catch (PermissionDeniedException $e) {
            return errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return errorResponse(__('An error occurred while loading the data. Please try again.'), 500);
        }
    }

}
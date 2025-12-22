<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\QualityManagerDashboardService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class QualityManagerDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(QualityManagerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the Admin dashboard.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        try {
            return view('dashboard.quality-manager');
        } catch (Exception $e) {
            logError('Error fetching dashboard data', 'QualityManagerDashboardController', $e);
            abort(500);
        }
    }

    /**
     * Get dashboard statistics.
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getStats();
            return successResponse('Statistics retrieved successfully', data: $stats);
        } catch (Exception $e) {
            logError('Error fetching dashboard stats', 'adminDashboardController', $e);
            return response()->json(['error' => 'Failed to fetch stats'], 500);
        }
    }

    public function getChartData()
    {
        try {
            $chartData = $this->dashboardService->getChartData();
            return successResponse('Chart data retrieved successfully', data: $chartData);
        } catch (\Exception $e) {
            logError('Error fetching chart data', 'QualityManagerDashboardController', $e);
            return response()->json(['message' => 'حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.'], 500);
        }
    }
}
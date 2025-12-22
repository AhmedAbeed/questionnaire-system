<?php

use App\Http\Controllers\Dashboard\QualityManagerDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('quality-manager')->name('quality-manager.')->middleware(['auth', 'role:quality_manager'])->group(function () {
    Route::get('/home', [QualityManagerDashboardController::class, 'index'])->name('home');
    Route::get('/dashboard/stats', [QualityManagerDashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [QualityManagerDashboardController::class, 'getChartData'])->name('dashboard.chart-data');
});
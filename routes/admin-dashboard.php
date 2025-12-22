<?php

use App\Http\Controllers\Dashboard\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('home');
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [AdminDashboardController::class, 'getChartData'])->name('dashboard.chart-data');
}); 
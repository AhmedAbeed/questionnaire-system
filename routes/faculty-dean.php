<?php

use App\Http\Controllers\Dashboard\FacultyDeanDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('faculty-dean')->name('faculty-dean.')->middleware(['auth', 'role:faculty_dean'])->group(function () {
    Route::get('/home', [FacultyDeanDashboardController::class, 'index'])->name('home');
    Route::get('/dashboard/stats', [FacultyDeanDashboardController::class, 'getStats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [FacultyDeanDashboardController::class, 'getChartData'])->name('dashboard.chart-data');
});
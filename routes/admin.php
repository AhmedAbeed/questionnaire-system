<?php

use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\UserManagement\AdminController;
use App\Http\Controllers\QuestionnaireTemplateController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|faculty_dean'])->group(function () {

    // Dashboard
    Route::get('/admin/home', [AdminDashboardController::class, 'index'])->name('admin.home');

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminController::class, 'index'])->name('index');
            Route::get('/data-table', [AdminController::class, 'dataTable'])->name('dataTable');
            Route::post('/store', [AdminController::class, 'store'])->name('store');
            Route::get('/stats', [AdminController::class, 'getStats'])->name('stats');

            Route::get('/{id}', [AdminController::class, 'show'])->name('show');
            Route::post('/{id}/update', [AdminController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
            Route::post('/{userId}/reset-password', [AdminController::class, 'resetPassword'])->name('resetPassword');
        });
    });

    // Questions Routes
    Route::prefix('questions')->name('questions.')->group(function () {
        Route::get('/', [QuestionController::class, 'index'])->name('index');
        Route::get('/create', [QuestionController::class, 'create'])->name('create');
        Route::post('/', [QuestionController::class, 'store'])->name('store');
        Route::get('/data-table', [QuestionController::class, 'dataTable'])->name('dataTable');
        Route::get('/stats', [QuestionController::class, 'stats'])->name('stats');
    });

    // Response Reports
    Route::get('/admin/dashboard/question-bank', [AdminDashboardController::class, 'getQuestionBankData'])
        ->name('admin.dashboard.question-bank');
});
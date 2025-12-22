<?php

use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|faculty_dean'])->prefix('questions')->name('questions.')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->name('index');
    Route::get('/create', [QuestionController::class, 'create'])->name('create');
    Route::post('/', [QuestionController::class, 'store'])->name('store');
    Route::get('/data-table', [QuestionController::class, 'dataTable'])->name('dataTable');
    Route::get('/stats', [QuestionController::class, 'stats'])->name('stats');
});

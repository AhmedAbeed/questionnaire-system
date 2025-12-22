<?php

use App\Http\Controllers\QuestionnaireTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|faculty_dean'])->prefix('questionnaire/template')->name('questionnaire.template.')->group(function () {
    Route::get('/', [QuestionnaireTemplateController::class, 'index'])->name('index');
    Route::get('/create', [QuestionnaireTemplateController::class, 'create'])->name('create');
    Route::get('/stats', [QuestionnaireTemplateController::class, 'getStats'])->name('stats');
    Route::post('/', [QuestionnaireTemplateController::class, 'store'])->name('store');
    Route::get('/data-table', [QuestionnaireTemplateController::class, 'dataTable'])->name('dataTable');
    Route::get('/{id}', [QuestionnaireTemplateController::class, 'show'])->name('show');
    Route::get('/{id}/data', [QuestionnaireTemplateController::class, 'getTemplateData'])->name('data');
});
<?php

use App\Http\Controllers\ResponseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|faculty_dean|quality_manager'])->group(function () {
    Route::get('/response/report/{QuestionnaireId}', [ResponseController::class, 'getReportByQuestionnaire'])->name('response.report');
});


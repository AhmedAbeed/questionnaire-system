<?php

use App\Http\Controllers\Dashboard\ResopondentDashboardController;
use App\Http\Controllers\DeployedQuestionnaireController;
use App\Http\Controllers\ResponseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:respondent'])->group(function () {
    Route::get('/respondent/home', [ResopondentDashboardController::class, 'index'])->name('respondent.home');
    Route::post('/questionnaire/submit', [DeployedQuestionnaireController::class, 'submit'])->name('questionnaire.submit');
    Route::post('/response/create', [ResponseController::class, 'store'])->name('response.create');
    Route::get('/questionnaire/deployed/{id}', [DeployedQuestionnaireController::class, 'getDeployedQuestionnaire'])->name('questionnaires.deployed.show');
});
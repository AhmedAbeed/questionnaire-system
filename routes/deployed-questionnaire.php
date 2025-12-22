<?php

use App\Http\Controllers\DeployedQuestionnaireController;
use App\Http\Controllers\ResponseController;
use Illuminate\Support\Facades\Route;

/*
| Deployed Questionnaire Routes
| Routes for managing deployed questionnaires, responses, and reports
*/

// Admin/Superadmin Routes (Shared functionality)
Route::prefix('questionnaires')->name('questionnaires.deployed.')->middleware(['auth', 'role:admin|faculty_dean|quality_manager'])->group(function () {
    
    // View Operations 
    Route::get('/', [DeployedQuestionnaireController::class, 'index'])
        ->name('index');

     // Data & Analytics 
     Route::get('/data-table', [DeployedQuestionnaireController::class, 'dataTable'])
     ->name('dataTable');
 
        Route::get('/stats', [DeployedQuestionnaireController::class, 'getStats'])
            ->name('stats');
        
        // Export Routes 
        Route::post('/export-non-responding', [DeployedQuestionnaireController::class, 'exportNonRespondingStudents'])
            ->name('export-non-responding');

            Route::get('/create', [DeployedQuestionnaireController::class, 'create'])
        ->name('create');
    
    Route::post('/', [DeployedQuestionnaireController::class, 'store'])
        ->name('store');
    
    // Parameterized routes at the end
    Route::delete('/{id}', [DeployedQuestionnaireController::class, 'destroy'])
        ->name('destroy');
        
        Route::get('/export-summary/{id}', [DeployedQuestionnaireController::class, 'exportCourseQuestionnaireSummary'])
            ->name('export-summary');
    
    Route::get('/summary/{id}', [DeployedQuestionnaireController::class, 'getSummary'])
        ->name('summary');
    
    Route::put('/update-close-date/{id}', [DeployedQuestionnaireController::class, 'updateCloseDate'])
        ->name('update-close-date');
    
});



// Utility Routes
Route::get('/faculties/programs', [DeployedQuestionnaireController::class, 'getProgramsByFaculty'])
    ->name('faculties.programs')
    ->middleware(['auth', 'role:admin|faculty_dean|quality_manager']);

// Respondent Routes (Students/Faculty)
Route::post('/questionnaires/submit', [DeployedQuestionnaireController::class, 'submit'])
    ->name('questionnaires.submit')
    ->middleware(['auth', 'role:respondent']);

// Show route accessible by both admins and respondents
Route::get('/questionnaires/{id}', [DeployedQuestionnaireController::class, 'getDeployedQuestionnaire'])
    ->name('questionnaires.deployed.show')
    ->middleware(['auth', 'role:admin|faculty_dean|quality_manager|respondent']);


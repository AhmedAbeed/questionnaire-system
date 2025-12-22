<?php


use Illuminate\Support\Facades\Route;
use Spatie\Browsershot\Browsershot;
use App\Http\Controllers\ResponseController;



// Authentication Routes
Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/questionnaire/{id}/report/download', [ResponseController::class, 'downloadReportByQuestionnaire'])->name('questionnaire.report.download');





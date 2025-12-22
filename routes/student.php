<?php

use App\Http\Controllers\Academic\StudentController;
use Illuminate\Support\Facades\Route;
Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    Route::prefix('student')->name('student.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index');
        Route::get('/stats', [StudentController::class, 'getStats'])->name('stats');
        Route::get('/data-table', [StudentController::class, 'dataTable'])->name('dataTable');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
        Route::get('/all', [StudentController::class, 'getStudents'])->name('all');
    });
});
<?php

use App\Http\Controllers\Academic\FacultyController;
use Illuminate\Support\Facades\Route;


Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    
    Route::prefix('faculties')->name('faculties.')->group(function () {
        
        Route::get('stats', [FacultyController::class, 'getStats'])->name('stats');
        Route::get('data-table', [FacultyController::class, 'dataTable'])->name('dataTable');
        
        Route::get('/', [FacultyController::class, 'index'])->name('index');
        Route::delete('{faculty}', [FacultyController::class, 'destroy'])->name('destroy');
    });
});
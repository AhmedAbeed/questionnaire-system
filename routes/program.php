<?php

use App\Http\Controllers\Academic\ProgramController;
use Illuminate\Support\Facades\Route;
Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    // Program Routes
    Route::prefix('programs')->name('programs.')->group(function () {
        Route::get('/by-faculty', [ProgramController::class, 'getByFaculty'])->name('by-faculty');
    });
});

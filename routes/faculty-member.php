<?php

use App\Http\Controllers\Academic\FacultyMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    Route::prefix('faculty-member')->name('faculty-member.')->group(function () {
        Route::get('/', [FacultyMemberController::class, 'index'])->name('index');
        Route::get('/stats', [FacultyMemberController::class, 'getStats'])->name('stats');
        Route::get('/data-table', [FacultyMemberController::class, 'dataTable'])->name('dataTable');
        Route::get('/{id}/datatable', [FacultyMemberController::class, 'facultyMemberSpecificDataTable'])->name('specific.datatable');
        Route::delete('/{faculty-member}', [FacultyMemberController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [FacultyMemberController::class, 'show'])->name('show');
    });
});
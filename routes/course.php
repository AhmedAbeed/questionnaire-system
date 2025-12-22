<?php

use App\Http\Controllers\Academic\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    Route::get('course/stats', [CourseController::class, 'getStats'])->name('courses.stats');
    Route::get('courses/data-table', [CourseController::class, 'dataTable'])->name('courses.dataTable');
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('courses/{course}/datatable', [CourseController::class, 'courseSpecificDataTable'])->name('courses.specific.datatable');
});
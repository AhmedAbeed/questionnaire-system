<?php

use App\Http\Controllers\Academic\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('academic')->name('academic.')->middleware(['auth', 'role:admin|faculty_dean'])->group(function () {
    
    Route::prefix('enrollments')->name('enrollments.')->group(function () {
        
        
        Route::get('stats', [EnrollmentController::class, 'getStats'])->name('stats');
        Route::get('data-table', [EnrollmentController::class, 'dataTable'])->name('dataTable');
        Route::get('students', [EnrollmentController::class, 'getStudents'])->name('students');
        Route::get('courses', [EnrollmentController::class, 'getCourses'])->name('courses');
        
        // Import routes - restricted to superadmin only
        Route::middleware(['role:admin'])->group(function () {
            Route::post('import', [EnrollmentController::class, 'import'])->name('import');
            Route::get('import/progress/{taskId}', [EnrollmentController::class, 'progress'])->name('import.progress');
            Route::post('import/instructor', [EnrollmentController::class, 'importInstructor'])->name('import.instructor');
            Route::get('import/instructor/progress/{importId}', [EnrollmentController::class, 'instructorProgress'])->name('import.instructor.progress');
        });
        // Standard CRUD routes
        Route::get('/', [EnrollmentController::class, 'index'])->name('index');
        Route::get('store', [EnrollmentController::class, 'store'])->name('store');
        Route::delete('{enrollment}', [EnrollmentController::class, 'destroy'])->name('destroy');
    });
});
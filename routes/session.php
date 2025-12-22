<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

// Session Management Routes
Route::prefix('session')->name('session.')->group(function () {
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
    Route::get('/remaining', [SessionController::class, 'remaining'])->name('remaining');
    Route::get('/timeout', [SessionController::class, 'timeout'])->name('timeout');
    Route::get('/timout', [SessionController::class, 'idleTimout'])->name('idle-timeout');
    Route::post('/extend', [SessionController::class, 'extend'])->name('extend');
    Route::get('/refresh-csrf', [SessionController::class, 'refreshCsrf'])->name('refresh-csrf');
}); 
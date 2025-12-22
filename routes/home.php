<?php

use Illuminate\Support\Facades\Route;

Route::get('/home', function () {
    if(auth()->user()->hasRole('admin')) {
        return redirect()->route('admin.home');
    } elseif (auth()->user()->hasRole('faculty_dean')) {
        return redirect()->route('faculty-dean.home');
    } elseif (auth()->user()->hasRole('respondent')) {
        return redirect()->route('respondent.home');
    } elseif(auth()->user()->hasRole('quality_manager')){
        return redirect()->route('quality-manager.home');
    }
})->name('home'); 
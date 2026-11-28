<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard.overview')->name('dashboard.overview');
Route::view('/feedback', 'dashboard.feedback')->name('dashboard.feedback');
Route::view('/feedback-review', 'dashboard.feedback-review')->name('dashboard.feedback-review');
Route::view('/ai-overview', 'dashboard.ai-overview')->name('dashboard.ai-overview');
Route::view('/task-a', 'dashboard.task-a')->name('dashboard.task-a');
Route::view('/task-b', 'dashboard.task-b')->name('dashboard.task-b');
Route::view('/documents', 'dashboard.documents')->name('dashboard.documents');
Route::view('/recommendations', 'dashboard.recommendations')->name('dashboard.recommendations');

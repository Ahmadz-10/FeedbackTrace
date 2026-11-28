<?php

use App\Http\Controllers\TaskA\ReviewController;
use Illuminate\Support\Facades\Route;

/*
 * API review memakai sesi web agar UI Blade dapat mengirim CSRF token.
 * Saat Sanctum tersedia, middleware dapat diganti dengan auth:sanctum.
 */
Route::middleware(['web', 'auth'])->prefix('v1/task-a')->name('task-a.')->group(function () {
    Route::post('findings/{finding:finding_code}/review', [ReviewController::class, 'store'])
        ->name('findings.review');
});

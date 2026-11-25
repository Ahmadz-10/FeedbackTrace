<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackBatch;
use App\Models\TaskA\FeedbackRecord;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    try {
        $databaseReady = collect([
            'feedback_batches',
            'feedback_records',
            'feedback_analyses',
            'consolidated_findings',
        ])->every(fn (string $table) => Schema::hasTable($table));
    } catch (\Throwable $exception) {
        report($exception);
        $databaseReady = false;
    }

    $totalBatches = $databaseReady ? FeedbackBatch::count() : 0;
    $processingBatches = $databaseReady
        ? FeedbackBatch::where('status', FeedbackBatch::STATUS_PROCESSING)->count()
        : 0;
    $completedBatches = $databaseReady
        ? FeedbackBatch::whereIn('status', [
            FeedbackBatch::STATUS_COMPLETED,
            FeedbackBatch::STATUS_PARTIALLY_COMPLETED,
        ])->count()
        : 0;
    $totalFeedback = $databaseReady ? FeedbackRecord::count() : 0;
    $processedFeedback = $databaseReady
        ? FeedbackAnalysis::whereIn('status', [
            FeedbackAnalysis::STATUS_ANALYZED,
            FeedbackAnalysis::STATUS_SKIPPED,
        ])->count()
        : 0;
    $pendingFindings = $databaseReady
        ? ConsolidatedFinding::where('review_status', ConsolidatedFinding::REVIEW_PENDING)->count()
        : 0;
    $approvedFindings = $databaseReady
        ? ConsolidatedFinding::where('review_status', ConsolidatedFinding::REVIEW_APPROVED)->count()
        : 0;
    $latestBatches = $databaseReady
        ? FeedbackBatch::query()->withCount(['records', 'findings'])->latest()->limit(6)->get()
        : collect();

    return view('dashboard', compact(
        'databaseReady',
        'totalBatches',
        'processingBatches',
        'completedBatches',
        'totalFeedback',
        'processedFeedback',
        'pendingFindings',
        'approvedFindings',
        'latestBatches',
    ));
})->name('dashboard');

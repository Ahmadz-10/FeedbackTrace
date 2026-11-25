<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackBatch;
use App\Models\TaskA\FeedbackRecord;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $totalBatches = FeedbackBatch::count();
    $processingBatches = FeedbackBatch::where('status', FeedbackBatch::STATUS_PROCESSING)->count();
    $completedBatches = FeedbackBatch::whereIn('status', [
        FeedbackBatch::STATUS_COMPLETED,
        FeedbackBatch::STATUS_PARTIALLY_COMPLETED,
    ])->count();
    $totalFeedback = FeedbackRecord::count();
    $processedFeedback = FeedbackAnalysis::whereIn('status', [
        FeedbackAnalysis::STATUS_ANALYZED,
        FeedbackAnalysis::STATUS_SKIPPED,
    ])->count();
    $pendingFindings = ConsolidatedFinding::where(
        'review_status',
        ConsolidatedFinding::REVIEW_PENDING
    )->count();
    $approvedFindings = ConsolidatedFinding::where(
        'review_status',
        ConsolidatedFinding::REVIEW_APPROVED
    )->count();

    $latestBatches = FeedbackBatch::query()
        ->withCount(['records', 'findings'])
        ->latest()
        ->limit(6)
        ->get();

    return view('dashboard', compact(
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

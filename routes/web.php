<?php

use App\\Models\\TaskA\\ConsolidatedFinding;
use App\\Models\\TaskA\\FeedbackAnalysis;
use App\\Models\\TaskA\\FeedbackAspectMention;
use App\\Models\\TaskA\\FeedbackBatch;
use App\\Models\\TaskA\\FeedbackRecord;
use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Support\\Facades\\Schema;

Route::get('/', function () {
    try {
        $databaseReady = collect([
            'feedback_batches',
            'feedback_records',
            'feedback_analyses',
            'feedback_aspect_mentions',
            'consolidated_findings',
        ])->every(fn (string $table) => Schema::hasTable($table));
    } catch (\\Throwable $exception) {
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
    $totalFindings = $databaseReady ? ConsolidatedFinding::count() : 0;
    $pendingFindings = $databaseReady
        ? ConsolidatedFinding::where('review_status', ConsolidatedFinding::REVIEW_PENDING)->count()
        : 0;
    $approvedFindings = $databaseReady
        ? ConsolidatedFinding::where('review_status', ConsolidatedFinding::REVIEW_APPROVED)->count()
        : 0;

    $processedFeedbackPercent = $totalFeedback > 0
        ? round(($processedFeedback / $totalFeedback) * 100, 1)
        : 0;

    $sentimentCounts = $databaseReady
        ? FeedbackAspectMention::query()
            ->select('sentiment')
            ->selectRaw('COUNT(*) as mention_count')
            ->groupBy('sentiment')
            ->pluck('mention_count', 'sentiment')
            ->all()
        : [];

    $sentimentMentionTotal = array_sum($sentimentCounts);
    $sentimentPercentages = ['positive' => 0, 'negative' => 0, 'neutral' => 0, 'mixed' => 0];

    if ($sentimentMentionTotal > 0) {
        foreach (array_keys($sentimentPercentages) as $sentiment) {
            $sentimentPercentages[$sentiment] = (int) round(
                (((int) ($sentimentCounts[$sentiment] ?? 0)) / $sentimentMentionTotal) * 100
            );
        }

        $difference = 100 - array_sum($sentimentPercentages);
        $largestSentiment = array_key_first(array_slice(
            $sentimentCounts,
            array_search(max($sentimentCounts), $sentimentCounts, true),
            1,
            true
        )) ?? 'positive';
        $sentimentPercentages[$largestSentiment] = max(
            0,
            $sentimentPercentages[$largestSentiment] + $difference
        );
    }

    $topAspects = $databaseReady
        ? FeedbackAspectMention::query()
            ->select('aspect_code')
            ->selectRaw('COUNT(*) as mention_count')
            ->groupBy('aspect_code')
            ->orderByDesc('mention_count')
            ->limit(4)
            ->get()
        : collect();
    $topAspectMax = (int) ($topAspects->max('mention_count') ?? 0);

    $latestBatches = $databaseReady
        ? FeedbackBatch::query()->withCount(['records', 'findings'])->latest()->limit(6)->get()
        : collect();
    $latestFindings = $databaseReady
        ? ConsolidatedFinding::query()->with('feedbackRecords')->latest()->limit(8)->get()
        : collect();

    return view('dashboard', compact(
        'databaseReady',
        'totalBatches',
        'processingBatches',
        'completedBatches',
        'totalFeedback',
        'processedFeedback',
        'processedFeedbackPercent',
        'totalFindings',
        'pendingFindings',
        'approvedFindings',
        'sentimentMentionTotal',
        'sentimentPercentages',
        'topAspects',
        'topAspectMax',
        'latestBatches',
        'latestFindings',
    ));
})->name('dashboard');

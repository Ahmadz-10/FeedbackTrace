<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackAspectMention;
use App\Models\TaskA\FeedbackEvidence;
use App\Models\TaskA\FindingReview;
use App\Services\TaskA\AnalysisResultPersister;
use App\Services\TaskA\InvalidAnalysisResponseException;
use App\Services\TaskA\ReanalysisBlockedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

it('stores batch metadata, analyses, mentions, evidence and findings from the real AI service output', function () {
    $batch = taPersistedBatch();

    expect($batch->status)->toBe('completed')
        ->and($batch->ai_job_id)->toBe('JOB-FIXTURE')
        ->and($batch->ai_request_id)->toBe('REQ-FIXTURE')
        ->and($batch->finished_at)->not->toBeNull()
        ->and($batch->error_message)->toBeNull()
        ->and($batch->stats['analyzed_feedbacks'])->toBe(3)
        ->and($batch->stats['skipped_feedbacks'])->toBe(1)
        ->and($batch->stats['total_findings'])->toBe(2)
        ->and($batch->stats['warnings'][0]['code'])->toBe('empty_comment')
        ->and($batch->model_metadata['model_id'])->toBe('model-aktual-v1')
        ->and($batch->model_metadata['prompt_version'])->toBe('extraction_v1')
        ->and($batch->model_metadata['decoding']['summary_prompt_version'])->toBe('finding_summary_v1');

    expect(FeedbackAnalysis::count())->toBe(4)
        ->and(FeedbackAspectMention::count())->toBe(3)
        ->and(FeedbackEvidence::count())->toBe(3)
        ->and(ConsolidatedFinding::count())->toBe(2);
});

it('records per-comment status, model info and quality flags, and never calls skipped comments analyzed', function () {
    $batch = taPersistedBatch();
    $f1 = $batch->records()->where('feedback_id', 'f1')->first();
    $f4 = $batch->records()->where('feedback_id', 'f4')->first();

    expect($f1->analysis->status)->toBe('analyzed')
        ->and($f1->analysis->provider)->toBe('groq')
        ->and($f1->analysis->model_id)->toBe('model-aktual-v1')
        ->and($f1->analysis->pipeline_version)->toBe('task-a-0.1.0')
        ->and($f4->analysis->status)->toBe('skipped')
        ->and($f4->analysis->model_id)->toBeNull()
        ->and($f4->quality_flags)->toBe(['empty_comment'])
        ->and($f4->analysis->quality_flags)->toBe(['empty_comment']);
});

it('stores evidence whose offsets slice the stored original comment back to the quote', function () {
    $batch = taPersistedBatch();

    foreach (FeedbackEvidence::with('record')->get() as $evidence) {
        expect($evidence->status)->toBe('valid')
            ->and($evidence->start_char)->toBeInt()
            ->and(mb_substr($evidence->record->comment_text, $evidence->start_char, $evidence->end_char - $evidence->start_char))
            ->toBe($evidence->quote);
    }
    expect($batch->records()->pluck('comment_text', 'feedback_id')->all())->toBe(TA_COMMENTS);
});

it('stores findings as pending_review with json fields, minority views and supporting feedback links', function () {
    $batch = taPersistedBatch();
    $weak = $batch->findings()->where('finding_type', 'service_weakness')->first();
    $strong = $batch->findings()->where('finding_type', 'service_strength')->first();

    expect($weak->review_status)->toBe('pending_review')
        ->and($weak->reviewed_at)->toBeNull()
        ->and($weak->unique_feedback_count)->toBe(2)
        ->and($weak->sentiment_distribution['negative'])->toBe(2)
        ->and($weak->minority_views[0]['feedback_id'])->toBe('f3')
        ->and($weak->single_feedback_support)->toBeFalse()
        ->and($weak->feedbackRecords->pluck('feedback_id')->sort()->values()->all())->toBe(['f1', 'f2'])
        ->and($strong->single_feedback_support)->toBeTrue()
        ->and($strong->feedbackRecords->pluck('feedback_id')->all())->toBe(['f3'])
        ->and($weak->finding_code)->toStartWith('F-');
});

it('stores the proposed aspect label for findings from proposed aspects', function () {
    $batch = taBatch();
    $response = taFixture();
    $response['findings'][0]['aspect_code'] = 'other';
    $response['findings'][0]['proposed_aspect_label'] = 'parking_availability';
    app(AnalysisResultPersister::class)->persist($batch, $response);

    expect($batch->findings()->where('proposed_aspect_label', 'parking_availability')->count())->toBe(1);
});

it('is idempotent: persisting the same result twice leaves identical data without duplicates', function () {
    $batch = taPersistedBatch();
    $codes = $batch->findings()->pluck('finding_code')->sort()->values()->all();

    app(AnalysisResultPersister::class)->persist($batch, taFixture());

    expect(FeedbackAnalysis::count())->toBe(4)
        ->and(FeedbackAspectMention::count())->toBe(3)
        ->and(FeedbackEvidence::count())->toBe(3)
        ->and(ConsolidatedFinding::count())->toBe(2)
        ->and(DB::table('finding_feedback')->count())->toBe(3)
        ->and($batch->fresh()->findings()->pluck('finding_code')->sort()->values()->all())->toBe($codes);
});

it('refuses to re-analyze a batch once a finding was approved or rejected', function () {
    $batch = taPersistedBatch();
    $batch->findings()->first()->forceFill(['review_status' => 'approved', 'reviewed_at' => now()])->save();

    expect(fn () => app(AnalysisResultPersister::class)->persist($batch, taFixture()))
        ->toThrow(ReanalysisBlockedException::class);
    expect(ConsolidatedFinding::count())->toBe(2)->and(FeedbackEvidence::count())->toBe(3);
});

it('also refuses to re-analyze when a pending finding was edited by staff', function () {
    $batch = taPersistedBatch();
    $finding = $batch->findings()->first();
    FindingReview::create(['finding_id' => $finding->id, 'action' => 'edit',
        'previous_status' => 'pending_review', 'new_status' => 'pending_review']);

    expect(fn () => app(AnalysisResultPersister::class)->assertCanReanalyze($batch))
        ->toThrow(ReanalysisBlockedException::class);
});

it('allows re-analysis while every finding is still untouched', function () {
    $batch = taPersistedBatch();

    expect(fn () => app(AnalysisResultPersister::class)->assertCanReanalyze($batch))->not->toThrow(ReanalysisBlockedException::class);
});

it('reports results for unknown feedback ids instead of failing, and links only known comments', function () {
    $batch = taBatch();
    $batch->records()->where('feedback_id', 'f3')->delete();

    app(AnalysisResultPersister::class)->persist($batch, taFixture());

    $batch->refresh();
    expect($batch->stats['unmatched_feedback_ids'])->toBe(['f3'])
        ->and(FeedbackAnalysis::count())->toBe(3)
        ->and($batch->findings()->where('finding_type', 'service_strength')->first()->feedbackRecords)->toHaveCount(0);
});

it('rolls back completely when the response is malformed, keeping the previous results intact', function (callable $corrupt) {
    $batch = taPersistedBatch();
    $before = ['stats' => $batch->stats, 'mentions' => FeedbackAspectMention::count(), 'findings' => ConsolidatedFinding::count()];

    $broken = taFixture();
    $corrupt($broken);

    expect(fn () => app(AnalysisResultPersister::class)->persist($batch, $broken))
        ->toThrow(InvalidAnalysisResponseException::class);

    expect($batch->fresh()->stats)->toBe($before['stats'])
        ->and(FeedbackAspectMention::count())->toBe($before['mentions'])
        ->and(ConsolidatedFinding::count())->toBe($before['findings']);
})->with([
    'missing required key' => [function (array &$r) {
        unset($r['results'][1]['mentions'][0]['sentiment']);
    }],
    'null in NOT NULL column' => [function (array &$r) {
        $r['results'][1]['mentions'][0]['sentiment'] = null;
    }],
    'finding without title' => [function (array &$r) {
        unset($r['findings'][1]['issue_title']);
    }],
]);

it('keeps the failed status and message of a batch whose analysis failed completely', function () {
    $batch = taBatch();
    $response = taFixture();
    $response['status'] = 'failed';
    foreach ($response['results'] as &$result) {
        $result = ['feedback_id' => $result['feedback_id'], 'status' => 'failed', 'quality_flags' => ['invalid_llm_output'], 'mentions' => [], 'error' => 'JSON tidak valid'];
    }
    unset($result);
    $response['findings'] = [];
    app(AnalysisResultPersister::class)->persist($batch, $response);

    expect($batch->fresh()->status)->toBe('failed')
        ->and(FeedbackAnalysis::where('status', 'failed')->count())->toBe(4)
        ->and(FeedbackAnalysis::first()->error_message)->toBe('JSON tidak valid')
        ->and(ConsolidatedFinding::count())->toBe(0);
});

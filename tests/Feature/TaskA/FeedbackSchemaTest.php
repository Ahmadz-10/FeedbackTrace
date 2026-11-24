<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackAspectMention;
use App\Models\TaskA\FeedbackBatch;
use App\Models\TaskA\FeedbackEvidence;
use App\Models\TaskA\FeedbackRecord;
use App\Models\TaskA\FindingReview;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function taskABatch(string $code = 'B-1'): FeedbackBatch
{
    return FeedbackBatch::create(['batch_code' => $code, 'title' => 'Demo']);
}

function taskARecord(FeedbackBatch $batch, string $id = 'f1', string $text = 'Antreannya lama sekali'): FeedbackRecord
{
    return $batch->records()->create(['feedback_id' => $id, 'question_id' => 'q1', 'comment_text' => $text]);
}

function taskAFinding(FeedbackBatch $batch, string $code = 'F-1'): ConsolidatedFinding
{
    return $batch->findings()->create([
        'finding_code' => $code, 'aspect_code' => 'service_speed',
        'reason_category' => 'long_waiting_time', 'finding_type' => 'service_weakness',
        'issue_title' => 'Antrean lama', 'issue_summary' => 'Mahasiswa menunggu lama.',
        'unique_feedback_count' => 1, 'mention_count' => 1,
        'sentiment_distribution' => ['positive' => 0, 'negative' => 1, 'neutral' => 0, 'mixed' => 0],
        'single_feedback_support' => true,
    ]);
}

it('supports the batch-to-evidence relation chain and JSON casts', function () {
    $batch = taskABatch();
    $record = taskARecord($batch);
    $analysis = FeedbackAnalysis::create([
        'feedback_record_id' => $record->id, 'status' => 'analyzed',
        'provider' => 'groq', 'model_id' => 'openai/gpt-oss-20b',
        'prompt_version' => 'p1', 'pipeline_version' => 'task-a-0.1.0',
        'quality_flags' => ['pii_redacted'],
    ]);
    $mention = FeedbackAspectMention::create([
        'feedback_analysis_id' => $analysis->id, 'feedback_record_id' => $record->id,
        'aspect_code' => 'service_speed', 'sentiment' => 'negative',
        'reason_type' => 'dissatisfaction_driver', 'reason_category' => 'long_waiting_time',
    ]);
    $evidence = FeedbackEvidence::create([
        'aspect_mention_id' => $mention->id, 'feedback_record_id' => $record->id,
        'quote' => 'lama sekali', 'start_char' => 11, 'end_char' => 22,
    ]);

    expect($record->batch->is($batch))->toBeTrue()
        ->and($record->analysis->is($analysis))->toBeTrue()
        ->and($analysis->aspectMentions)->toHaveCount(1)
        ->and($mention->evidence->first()->is($evidence))->toBeTrue()
        ->and($analysis->quality_flags)->toBe(['pii_redacted'])
        ->and($evidence->fresh()->status)->toBe(FeedbackEvidence::STATUS_UNVERIFIED);
});

it('keeps raw text unchanged and enforces batch-scoped feedback IDs', function () {
    $batchA = taskABatch('A');
    $batchB = taskABatch('B');
    $raw = "  Antreannya   lama\n\nbanget  ";
    $record = taskARecord($batchA, 'same', $raw);
    $record->update(['normalized_text' => 'Antreannya lama banget']);

    expect($record->fresh()->comment_text)->toBe($raw)
        ->and($record->fresh()->normalized_text)->toBe('Antreannya lama banget');
    expect(fn () => taskARecord($batchA, 'same'))->toThrow(QueryException::class);
    expect(taskARecord($batchB, 'same')->exists)->toBeTrue();
});

it('enforces one analysis per record and protects review status from mass assignment', function () {
    $batch = taskABatch();
    $record = taskARecord($batch);
    FeedbackAnalysis::create(['feedback_record_id' => $record->id, 'status' => 'analyzed']);
    expect(fn () => FeedbackAnalysis::create(['feedback_record_id' => $record->id, 'status' => 'failed']))
        ->toThrow(QueryException::class);

    $finding = $batch->findings()->create([
        'finding_code' => 'F-X', 'aspect_code' => 'service_speed',
        'reason_category' => 'long_waiting_time', 'finding_type' => 'service_weakness',
        'issue_title' => 't', 'issue_summary' => 's',
        'sentiment_distribution' => ['negative' => 1],
        'review_status' => 'approved', 'reviewed_at' => now(),
    ])->fresh();

    expect($finding->review_status)->toBe(ConsolidatedFinding::REVIEW_PENDING)
        ->and($finding->reviewed_at)->toBeNull()
        ->and(ConsolidatedFinding::approved()->count())->toBe(0);

    $finding->forceFill(['review_status' => ConsolidatedFinding::REVIEW_APPROVED, 'reviewed_at' => now()])->save();
    expect(ConsolidatedFinding::approved()->count())->toBe(1);
});

it('stores finding support links and review audit rows', function () {
    $batch = taskABatch();
    $r1 = taskARecord($batch, 'f1');
    $r2 = taskARecord($batch, 'f2');
    $finding = taskAFinding($batch);
    $finding->update(['minority_views' => [['feedback_id' => 'f2', 'sentiment' => 'positive', 'summary' => 'cepat']]]);
    $finding->feedbackRecords()->attach([$r1->id, $r2->id]);
    expect($finding->fresh()->feedbackRecords)->toHaveCount(2)
        ->and($finding->fresh()->minority_views[0]['feedback_id'])->toBe('f2');

    FindingReview::create([
        'finding_id' => $finding->id, 'action' => 'edit',
        'previous_status' => 'pending_review', 'new_status' => 'pending_review',
        'changes' => ['issue_title' => ['old' => 'Antrean', 'new' => 'Antrean akademik']],
    ]);
    expect($finding->reviews()->count())->toBe(1);
    expect(fn () => $finding->feedbackRecords()->attach($r1->id))->toThrow(QueryException::class);
});

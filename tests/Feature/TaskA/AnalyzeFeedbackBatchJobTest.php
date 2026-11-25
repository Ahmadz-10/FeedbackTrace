<?php

use App\Jobs\TaskA\AnalyzeFeedbackBatch;
use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackBatch;
use App\Services\TaskA\AiServiceClient;
use App\Services\TaskA\AiServiceException;
use App\Services\TaskA\AnalysisResultPersister;
use App\Services\TaskA\ReanalysisBlockedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

const TA_API = 'ai.test/api/v1/task-a/';

beforeEach(function () {
    config()->set('services.ai_service', ['url' => 'http://ai.test', 'key' => 'k', 'timeout' => 5]);
});

function taRun(FeedbackBatch $batch): AnalyzeFeedbackBatch
{
    $job = (new AnalyzeFeedbackBatch($batch->id))->withFakeQueueInteractions();
    $job->handle(app(AiServiceClient::class), app(AnalysisResultPersister::class));

    return $job;
}

function taAccepted(string $id = 'JOB-FIXTURE'): array
{
    return ['job_id' => $id, 'batch_id' => 'B1', 'status' => 'pending', 'warnings' => []];
}

it('submits the batch with the original comments, records the job id and keeps polling while it runs', function () {
    $batch = taBatch();
    $batch->records()->where('feedback_id', 'f1')->update(['language_hint' => 'id', 'submitted_at' => '2026-11-20 08:30:00']);
    $batch->update(['source' => 'siakad', 'options' => ['max_retries' => 1]]);
    Http::fake([
        TA_API.'analyze' => Http::response(taAccepted(), 202),
        TA_API.'jobs/*' => Http::response(['job_id' => 'JOB-FIXTURE', 'status' => 'processing']),
    ]);

    $job = taRun($batch);

    $job->assertReleased(AnalyzeFeedbackBatch::POLL_SECONDS);
    $batch->refresh();
    expect($batch->ai_job_id)->toBe('JOB-FIXTURE')->and($batch->status)->toBe('processing')
        ->and($batch->started_at)->not->toBeNull()->and($batch->ai_request_id)->toStartWith('batch-'.$batch->id.'-');
    Http::assertSent(function (Request $r) use ($batch) {
        if (! str_ends_with($r->url(), '/analyze')) {
            return false;
        }
        $f1 = $r['feedbacks'][0];

        return $r->hasHeader('X-Service-Key', 'k')
            && $r['batch'] === ['batch_id' => 'B1', 'title' => 'Fixture', 'source' => 'siakad', 'schema_version' => '1.0']
            && $r['options'] === ['max_retries' => 1]
            && $r['request_id'] === $batch->ai_request_id
            && count($r['feedbacks']) === 4
            && $f1 === ['feedback_id' => 'f1', 'question_id' => 'q1', 'comment_text' => 'Antreannya lama banget',
                'submitted_at' => '2026-11-20T08:30:00+00:00', 'language_hint' => 'id']
            && $r['feedbacks'][3]['comment_text'] === ''
            && ! array_key_exists('language_hint', $r['feedbacks'][1]);
    });
});

it('omits options when the batch has none', function () {
    Http::fake([TA_API.'analyze' => Http::response(taAccepted(), 202), TA_API.'jobs/*' => Http::response(['status' => 'pending'])]);

    taRun(taBatch());

    Http::assertSent(fn (Request $r) => ! str_ends_with($r->url(), '/analyze') || ! array_key_exists('options', $r->data()));
});

it('fetches and stores the results once the AI job has finished', function () {
    $batch = taBatch();
    $batch->update(['ai_job_id' => 'JOB-FIXTURE', 'status' => 'processing']);
    Http::fake([
        TA_API.'jobs/*' => Http::response(['job_id' => 'JOB-FIXTURE', 'status' => 'completed']),
        TA_API.'batches/B1/results' => Http::response(taFixture()),
    ]);

    $job = taRun($batch);

    $job->assertNotReleased()->assertNotFailed();
    expect($batch->fresh()->status)->toBe('completed')
        ->and(ConsolidatedFinding::count())->toBe(2)
        ->and(FeedbackAnalysis::count())->toBe(4);
});

it('goes from submission to stored results in one run when the job is already done', function () {
    $batch = taBatch();
    Http::fake([
        TA_API.'analyze' => Http::response(taAccepted(), 202),
        TA_API.'jobs/*' => Http::response(['status' => 'completed']),
        TA_API.'batches/B1/results' => Http::response(taFixture()),
    ]);

    taRun($batch);

    expect($batch->fresh()->status)->toBe('completed')->and(ConsolidatedFinding::count())->toBe(2);
});

it('follows the running job when the AI service says the same batch is already being processed', function () {
    $batch = taBatch();
    Http::fake([
        TA_API.'analyze' => Http::response(['error' => 'batch_busy', 'job_id' => 'JOB-RUNNING'], 409),
        TA_API.'jobs/JOB-RUNNING' => Http::response(['status' => 'processing']),
    ]);

    $job = taRun($batch);

    $job->assertReleased();
    expect($batch->fresh()->ai_job_id)->toBe('JOB-RUNNING')->and($batch->fresh()->status)->toBe('processing');
});

it('fails the batch immediately, with the reason codes, when the AI service rejects it', function () {
    $batch = taBatch();
    Http::fake([TA_API.'analyze' => Http::response(
        ['error' => 'invalid_batch', 'errors' => [['code' => 'duplicate_feedback_id'], ['code' => 'empty_batch']]], 422)]);

    $job = taRun($batch);

    $job->assertFailed();
    $batch->refresh();
    expect($batch->status)->toBe('failed')->and($batch->error_message)->toContain('duplicate_feedback_id', 'empty_batch')
        ->and($batch->finished_at)->not->toBeNull();
});

it('lets the queue retry on temporary AI service errors without marking the batch failed', function () {
    $batch = taBatch();
    Http::fake([TA_API.'analyze' => Http::response('maintenance', 503)]);

    expect(fn () => taRun($batch))->toThrow(AiServiceException::class);

    expect($batch->fresh()->status)->toBe('pending')->and($batch->fresh()->ai_job_id)->toBeNull();
});

it('fails the batch with a clear message when the job was lost, for example after an AI service restart', function () {
    $batch = taBatch();
    $batch->update(['ai_job_id' => 'JOB-GONE', 'status' => 'processing']);
    Http::fake([TA_API.'jobs/*' => Http::response(['error' => 'job_not_found'], 404)]);

    $job = taRun($batch);

    $job->assertFailed();
    expect($batch->fresh()->status)->toBe('failed')->and($batch->fresh()->error_message)->toContain('restart');
});

it('uses the AI job error when the whole job failed without a result', function () {
    $batch = taBatch();
    $batch->update(['ai_job_id' => 'JOB-FIXTURE', 'status' => 'processing']);
    Http::fake([
        TA_API.'jobs/*' => Http::response(['status' => 'failed', 'error' => 'Analisis gagal: RuntimeError']),
        TA_API.'batches/B1/results' => Http::response(['error' => 'job_failed_without_result'], 409),
    ]);

    taRun($batch)->assertFailed();

    expect($batch->fresh()->error_message)->toBe('Analisis gagal: RuntimeError');
});

it('does not store results that belong to a different job than the one it submitted', function () {
    $batch = taBatch();
    $batch->update(['ai_job_id' => 'JOB-OTHER', 'status' => 'processing']);
    Http::fake([
        TA_API.'jobs/*' => Http::response(['status' => 'completed']),
        TA_API.'batches/B1/results' => Http::response(taFixture()),
    ]);

    taRun($batch)->assertFailed();

    expect(ConsolidatedFinding::count())->toBe(0)->and($batch->fresh()->status)->toBe('failed');
});

it('fails the batch without partial data when the AI response is malformed', function () {
    $batch = taBatch();
    $batch->update(['ai_job_id' => 'JOB-FIXTURE', 'status' => 'processing']);
    $broken = taFixture();
    unset($broken['results'][0]['mentions'][0]['aspect_code']);
    Http::fake([TA_API.'jobs/*' => Http::response(['status' => 'completed']), TA_API.'batches/B1/results' => Http::response($broken)]);

    taRun($batch)->assertFailed();

    expect($batch->fresh()->status)->toBe('failed')->and($batch->fresh()->error_message)->toContain('tidak dapat disimpan')
        ->and(FeedbackAnalysis::count())->toBe(0);
});

it('does not overwrite findings that staff already reviewed', function () {
    $batch = taPersistedBatch();
    $batch->findings()->first()->forceFill(['review_status' => 'approved', 'reviewed_at' => now()])->save();
    $batch->update(['ai_job_id' => 'JOB-FIXTURE']);
    Http::fake([TA_API.'jobs/*' => Http::response(['status' => 'completed']), TA_API.'batches/B1/results' => Http::response(taFixture())]);

    taRun($batch)->assertFailed();

    expect(ConsolidatedFinding::where('review_status', 'approved')->count())->toBe(1)
        ->and($batch->fresh()->error_message)->toContain('sudah ditinjau');
});

it('start() resets the batch and queues the job, but refuses when reviewed findings exist', function () {
    Queue::fake();
    $batch = taPersistedBatch();

    AnalyzeFeedbackBatch::start($batch);
    $batch->refresh();
    expect($batch->status)->toBe('pending')->and($batch->ai_job_id)->toBeNull()->and($batch->finished_at)->toBeNull();
    Queue::assertPushed(AnalyzeFeedbackBatch::class, fn ($job) => $job->batchId === $batch->id);

    Queue::fake();
    $batch->findings()->first()->forceFill(['review_status' => 'rejected', 'reviewed_at' => now()])->save();
    $batch->update(['status' => 'completed']);
    expect(fn () => AnalyzeFeedbackBatch::start($batch))->toThrow(ReanalysisBlockedException::class);
    Queue::assertNothingPushed();
    expect($batch->fresh()->status)->toBe('completed');
});

it('marks the batch failed from the queue failed() hook without overwriting a more specific reason', function () {
    $batch = taBatch();
    (new AnalyzeFeedbackBatch($batch->id))->failed(new RuntimeException('koneksi terputus'));
    expect($batch->fresh()->status)->toBe('failed')->and($batch->fresh()->error_message)->toContain('RuntimeException', 'koneksi terputus');

    $batch->update(['error_message' => 'AI service menolak batch (empty_batch)']);
    (new AnalyzeFeedbackBatch($batch->id))->failed(new RuntimeException('lain'));
    expect($batch->fresh()->error_message)->toBe('AI service menolak batch (empty_batch)');
});

it('ignores a batch that was deleted before the job ran and keeps retrying for a bounded time', function () {
    Http::fake();
    $job = new AnalyzeFeedbackBatch(999999);
    $job->handle(app(AiServiceClient::class), app(AnalysisResultPersister::class));

    Http::assertNothingSent();
    expect($job->retryUntil()->getTimestamp())->toBeGreaterThan(now()->addMinutes(59)->getTimestamp())
        ->and($job->backoff())->toBe([15, 30, 60]);
});

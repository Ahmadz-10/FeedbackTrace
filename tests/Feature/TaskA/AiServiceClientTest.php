<?php

use App\Services\TaskA\AiServiceBusyException;
use App\Services\TaskA\AiServiceClient;
use App\Services\TaskA\AiServiceException;
use App\Services\TaskA\AiServiceJobLostException;
use App\Services\TaskA\AiServiceRejectedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.ai_service', ['url' => 'http://ai.test/', 'key' => 'kunci-rahasia', 'timeout' => 5]);
});

it('sends the service key and posts the payload to the analyze endpoint', function () {
    Http::fake(['ai.test/*' => Http::response(['job_id' => 'J1', 'batch_id' => 'B1', 'status' => 'pending'], 202)]);

    $out = app(AiServiceClient::class)->submit(['batch' => ['batch_id' => 'B1'], 'feedbacks' => []]);

    expect($out['job_id'])->toBe('J1');
    Http::assertSent(fn (Request $r) => $r->url() === 'http://ai.test/api/v1/task-a/analyze'
        && $r->method() === 'POST'
        && $r->hasHeader('X-Service-Key', 'kunci-rahasia')
        && $r['batch']['batch_id'] === 'B1');
});

it('omits the service key header when none is configured', function () {
    config()->set('services.ai_service.key', null);
    Http::fake(['ai.test/*' => Http::response(['status' => 'pending'])]);

    app(AiServiceClient::class)->job('J1');

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('X-Service-Key'));
});

it('calls the read endpoints with url-encoded identifiers', function () {
    Http::fake(['ai.test/*' => Http::response(['ok' => true])]);
    $client = app(AiServiceClient::class);

    $client->job('J 1');
    $client->results('B/1');
    $client->summary('B1');
    $client->finding('F-abc');

    $urls = Http::recorded()->map(fn ($pair) => $pair[0]->url())->all();
    expect($urls)->toBe([
        'http://ai.test/api/v1/task-a/jobs/J%201',
        'http://ai.test/api/v1/task-a/batches/B%2F1/results',
        'http://ai.test/api/v1/task-a/batches/B1/summary',
        'http://ai.test/api/v1/task-a/findings/F-abc',
    ]);
});

it('maps AI service errors to specific non-retryable exceptions', function () {
    Http::fake([
        'ai.test/api/v1/task-a/analyze' => Http::sequence()
            ->push(['error' => 'invalid_batch', 'errors' => [['code' => 'duplicate_feedback_id']]], 422)
            ->push(['error' => 'batch_busy', 'job_id' => 'J-RUN'], 409),
        'ai.test/api/v1/task-a/jobs/*' => Http::response(['error' => 'job_not_found'], 404),
    ]);
    $client = app(AiServiceClient::class);

    try {
        $client->submit([]);
        $this->fail('expected rejection');
    } catch (AiServiceRejectedException $e) {
        expect($e->errors()[0]['code'])->toBe('duplicate_feedback_id')->and($e->isRetryable())->toBeFalse();
    }
    try {
        $client->submit([]);
        $this->fail('expected busy');
    } catch (AiServiceBusyException $e) {
        expect($e->jobId())->toBe('J-RUN')->and($e->isRetryable())->toBeFalse();
    }
    expect(fn () => $client->job('x'))->toThrow(AiServiceJobLostException::class);
});

it('treats connection failures, 5xx and a rejected key as AiServiceException', function () {
    Http::fake([
        'ai.test/api/v1/task-a/jobs/a' => Http::response('boom', 500),
        'ai.test/api/v1/task-a/jobs/b' => Http::response(['detail' => 'Missing or invalid service API key.'], 401),
        'ai.test/api/v1/task-a/jobs/c' => fn () => throw new ConnectionException('refused'),
    ]);
    $client = app(AiServiceClient::class);

    expect(fn () => $client->job('a'))->toThrow(AiServiceException::class, 'HTTP 500');
    try {
        $client->job('b');
        $this->fail('expected rejected key');
    } catch (AiServiceException $e) {
        expect($e->getMessage())->toContain('AI_SERVICE_KEY')->and($e->isRetryable())->toBeFalse();
    }
    try {
        $client->job('c');
    } catch (AiServiceException $e) {
        expect($e->isRetryable())->toBeTrue()->and($e->getMessage())->toContain('ConnectionException');
    }
});

it('does not treat a 404 on non-job endpoints as a lost job', function () {
    Http::fake(['ai.test/*' => Http::response(['error' => 'finding_not_found'], 404)]);

    expect(fn () => app(AiServiceClient::class)->finding('F-x'))->toThrow(AiServiceException::class)
        ->and(fn () => app(AiServiceClient::class)->finding('F-x'))->not->toThrow(AiServiceJobLostException::class);
});

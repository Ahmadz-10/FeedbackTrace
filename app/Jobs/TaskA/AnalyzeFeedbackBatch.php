<?php

namespace App\Jobs\TaskA;

use App\Models\TaskA\FeedbackBatch;
use App\Services\TaskA\AiServiceBusyException;
use App\Services\TaskA\AiServiceClient;
use App\Services\TaskA\AiServiceException;
use App\Services\TaskA\AiServiceJobLostException;
use App\Services\TaskA\AnalysisResultPersister;
use App\Services\TaskA\InvalidAnalysisResponseException;
use App\Services\TaskA\ReanalysisBlockedException;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class AnalyzeFeedbackBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public int $timeout = 120;
    public const POLL_SECONDS = 10;

    public function __construct(public int $batchId) {}

    public static function start(FeedbackBatch $batch): void
    {
        app(AnalysisResultPersister::class)->assertCanReanalyze($batch);
        $batch->update([
            'status' => FeedbackBatch::STATUS_PENDING,
            'ai_job_id' => null,
            'ai_request_id' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);
        static::dispatch($batch->id);
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }

    public function backoff(): array
    {
        return [15, 30, 60];
    }

    public function handle(AiServiceClient $client, AnalysisResultPersister $persister): void
    {
        $batch = FeedbackBatch::find($this->batchId);
        if ($batch === null) {
            return;
        }

        if ($batch->ai_job_id === null && ! $this->submit($batch, $client)) {
            return;
        }

        try {
            $job = $client->job($batch->ai_job_id);
        } catch (AiServiceJobLostException) {
            $this->abort($batch, 'Job hilang di AI service (kemungkinan service restart). Jalankan ulang analisis.');
            return;
        }

        if (in_array($job['status'], ['pending', 'processing'], true)) {
            $this->release(self::POLL_SECONDS);
            return;
        }

        try {
            $results = $client->results($batch->batch_code);
        } catch (AiServiceException $e) {
            if (($e->body['error'] ?? null) === 'job_failed_without_result') {
                $this->abort($batch, $job['error'] ?? 'Analisis gagal tanpa hasil.');
                return;
            }
            throw $e;
        }

        if (($results['job_id'] ?? null) !== $batch->ai_job_id) {
            $this->abort($batch, 'Hasil yang tersedia berasal dari job lain; jalankan ulang analisis.');
            return;
        }

        try {
            $persister->persist($batch, $results);
        } catch (ReanalysisBlockedException|InvalidAnalysisResponseException $e) {
            $this->abort($batch, $e->getMessage());
        }
    }

    public function failed(?Throwable $e): void
    {
        $batch = FeedbackBatch::find($this->batchId);
        if ($batch === null || ($batch->status === FeedbackBatch::STATUS_FAILED && filled($batch->error_message))) {
            return;
        }
        $batch->update([
            'status' => FeedbackBatch::STATUS_FAILED,
            'error_message' => 'Analisis dihentikan: '.($e ? class_basename($e).': '.Str::limit($e->getMessage(), 200) : 'tidak diketahui'),
            'finished_at' => now(),
        ]);
    }

    private function submit(FeedbackBatch $batch, AiServiceClient $client): bool
    {
        try {
            $accepted = $client->submit($this->buildPayload($batch));
            $jobId = $accepted['job_id'];
        } catch (AiServiceBusyException $busy) {
            $jobId = $busy->jobId();
            if ($jobId === null) {
                $this->abort($batch, $busy->getMessage());
                return false;
            }
        } catch (AiServiceException $e) {
            if ($e->isRetryable()) {
                throw $e;
            }
            $this->abort($batch, $this->describeRejection($e));
            return false;
        }

        $batch->update([
            'ai_job_id' => $jobId,
            'status' => FeedbackBatch::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        return true;
    }

    private function buildPayload(FeedbackBatch $batch): array
    {
        $keepFilled = fn (array $values) => array_filter($values, fn ($value) => $value !== null);
        $payload = [
            'request_id' => 'batch-'.$batch->id.'-'.Str::uuid(),
            'batch' => $keepFilled([
                'batch_id' => $batch->batch_code,
                'title' => $batch->title,
                'source' => $batch->source,
                'schema_version' => $batch->schema_version,
            ]),
            'feedbacks' => $batch->records()->orderBy('id')->get()->map(fn ($record) => $keepFilled([
                'feedback_id' => $record->feedback_id,
                'question_id' => $record->question_id,
                'comment_text' => $record->comment_text,
                'submitted_at' => $record->submitted_at?->toIso8601String(),
                'language_hint' => $record->language_hint,
            ]))->all(),
        ];

        if (! empty($batch->options)) {
            $payload['options'] = $batch->options;
        }
        $batch->ai_request_id = $payload['request_id'];

        return $payload;
    }

    private function describeRejection(AiServiceException $e): string
    {
        $codes = collect($e->body['errors'] ?? [])->pluck('code')->unique()->implode(', ');
        return 'AI service menolak batch'.($codes !== '' ? " ({$codes})" : '').': '.$e->getMessage();
    }

    private function abort(FeedbackBatch $batch, string $message): void
    {
        $batch->update([
            'status' => FeedbackBatch::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
        $this->fail(new \RuntimeException($message));
    }
}

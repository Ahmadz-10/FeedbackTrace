<?php

namespace App\Services\TaskA;

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackAnalysis;
use App\Models\TaskA\FeedbackAspectMention;
use App\Models\TaskA\FeedbackBatch;
use App\Models\TaskA\FeedbackEvidence;
use App\Models\TaskA\FeedbackRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AnalysisResultPersister
{
    public function assertCanReanalyze(FeedbackBatch $batch): void
    {
        $reviewed = $batch->findings()
            ->where(fn ($q) => $q->where('review_status', '!=', ConsolidatedFinding::REVIEW_PENDING)->orWhereHas('reviews'))
            ->exists();

        if ($reviewed) {
            throw new ReanalysisBlockedException(
                'Batch ini memiliki temuan yang sudah ditinjau; analisis ulang akan menimpa keputusan staf.'
            );
        }
    }

    public function persist(FeedbackBatch $batch, array $response): void
    {
        $this->assertCanReanalyze($batch);

        try {
            $this->store($batch, $response);
        } catch (Throwable $e) {
            if ($this->isMalformedData($e)) {
                throw new InvalidAnalysisResponseException(
                    'Hasil analisis tidak dapat disimpan: '.class_basename($e).': '.Str::limit($e->getMessage(), 150),
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    private function isMalformedData(Throwable $e): bool
    {
        return $e instanceof \ErrorException
            || $e instanceof \TypeError
            || $e instanceof \ValueError
            || $e instanceof \InvalidArgumentException
            || ($e instanceof QueryException && str_starts_with((string) $e->getCode(), '23'));
    }

    private function store(FeedbackBatch $batch, array $response): void
    {
        DB::transaction(function () use ($batch, $response) {
            FeedbackAnalysis::whereIn('feedback_record_id', $batch->records()->select('id'))->delete();
            $batch->findings()->delete();

            /** @var Collection<string, FeedbackRecord> $records */
            $records = $batch->records()->get()->keyBy('feedback_id');
            $meta = $response['metadata'] ?? null;
            $unmatched = [];

            foreach ($response['results'] ?? [] as $result) {
                $record = $records->get($result['feedback_id']);
                if ($record === null) {
                    $unmatched[] = $result['feedback_id'];
                    continue;
                }
                $this->storeResult($record, $result, $meta);
            }

            foreach ($response['findings'] ?? [] as $finding) {
                $this->storeFinding($batch, $records, $finding);
            }

            $stats = $response['stats'] ?? [];
            $stats['warnings'] = $response['warnings'] ?? [];
            if ($unmatched !== []) {
                $stats['unmatched_feedback_ids'] = $unmatched;
            }

            $batch->update([
                'status' => $response['status'],
                'stats' => $stats,
                'model_metadata' => $meta,
                'ai_job_id' => $response['job_id'] ?? $batch->ai_job_id,
                'ai_request_id' => $response['request_id'] ?? $batch->ai_request_id,
                'error_message' => null,
                'finished_at' => now(),
            ]);
        });
    }

    private function storeResult(FeedbackRecord $record, array $result, ?array $meta): void
    {
        $record->update(['quality_flags' => $result['quality_flags'] ?? []]);

        $called = $result['status'] !== 'skipped';
        $analysis = FeedbackAnalysis::create([
            'feedback_record_id' => $record->id,
            'status' => $result['status'],
            'provider' => $called ? ($meta['provider'] ?? null) : null,
            'model_id' => $called ? ($meta['model_id'] ?? null) : null,
            'prompt_version' => $meta['prompt_version'] ?? null,
            'pipeline_version' => $meta['pipeline_version'] ?? null,
            'schema_version' => $meta['schema_version'] ?? null,
            'quality_flags' => $result['quality_flags'] ?? [],
            'error_message' => $result['error'] ?? null,
        ]);

        foreach ($result['mentions'] ?? [] as $m) {
            $mention = FeedbackAspectMention::create([
                'feedback_analysis_id' => $analysis->id,
                'feedback_record_id' => $record->id,
                'mention_code' => $m['mention_id'] ?? null,
                'aspect_code' => $m['aspect_code'],
                'is_proposed_aspect' => (bool) ($m['is_proposed_aspect'] ?? false),
                'proposed_aspect_label' => $m['proposed_aspect_label'] ?? null,
                'sentiment' => $m['sentiment'],
                'sentiment_strength' => $m['sentiment_strength'] ?? null,
                'reason_type' => $m['reason_type'],
                'reason_category' => $m['reason_category'],
                'reason_description' => $m['reason_description'] ?? null,
            ]);

            foreach ($m['evidence'] ?? [] as $e) {
                FeedbackEvidence::create([
                    'aspect_mention_id' => $mention->id,
                    'feedback_record_id' => $record->id,
                    'quote' => $e['quote'],
                    'start_char' => $e['start_char'] ?? null,
                    'end_char' => $e['end_char'] ?? null,
                    'status' => $e['status'] ?? FeedbackEvidence::STATUS_UNVERIFIED,
                ]);
            }
        }
    }

    private function storeFinding(FeedbackBatch $batch, Collection $records, array $f): void
    {
        $finding = $batch->findings()->create([
            'finding_code' => $f['finding_id'],
            'aspect_code' => $f['aspect_code'],
            'proposed_aspect_label' => $f['proposed_aspect_label'] ?? null,
            'reason_category' => $f['reason_category'],
            'finding_type' => $f['finding_type'],
            'issue_title' => $f['issue_title'],
            'issue_summary' => $f['issue_summary'],
            'unique_feedback_count' => $f['unique_feedback_count'],
            'mention_count' => $f['mention_count'],
            'sentiment_distribution' => $f['sentiment_distribution'],
            'minority_views' => $f['minority_views'] ?? [],
            'single_feedback_support' => (bool) ($f['single_feedback_support'] ?? false),
            'quality_flags' => $f['quality_flags'] ?? [],
        ]);

        $ids = collect($f['supporting_feedback_ids'] ?? [])
            ->map(fn ($fid) => $records->get($fid)?->id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $finding->feedbackRecords()->attach($ids);
    }
}

<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FeedbackBatch;
use App\Services\TaskA\AnalysisResultPersister;

const TA_COMMENTS = [
    'f1' => 'Antreannya lama banget',
    'f2' => 'Nunggu berjam-jam baru dilayani',
    'f3' => 'Loketnya cepat',
    'f4' => '',
];

function taFixture(): array
{
    return json_decode(file_get_contents(__DIR__.'/fixtures/analyze_response.json'), true, flags: JSON_THROW_ON_ERROR);
}

function taBatch(string $code = 'B1'): FeedbackBatch
{
    $batch = FeedbackBatch::create(['batch_code' => $code, 'title' => 'Fixture']);
    foreach (TA_COMMENTS as $id => $text) {
        $batch->records()->create(['feedback_id' => $id, 'question_id' => 'q1', 'comment_text' => $text]);
    }

    return $batch;
}

function taPersistedBatch(string $code = 'B1'): FeedbackBatch
{
    $batch = taBatch($code);
    app(AnalysisResultPersister::class)->persist($batch, taFixture());

    return $batch->fresh();
}

function taFinding(string $type = 'service_weakness'): ConsolidatedFinding
{
    return taPersistedBatch()->findings()->where('finding_type', $type)->firstOrFail();
}

<?php

namespace App\Services\TaskA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AiServiceClient
{
    public function submit(array $payload): array
    {
        return $this->send(fn (PendingRequest $http) => $http->post('analyze', $payload));
    }

    public function job(string $jobId): array
    {
        return $this->send(
            fn (PendingRequest $http) => $http->get('jobs/'.rawurlencode($jobId)),
            notFoundIsJobLost: true
        );
    }

    public function results(string $batchCode): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get('batches/'.rawurlencode($batchCode).'/results'));
    }

    public function summary(string $batchCode): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get('batches/'.rawurlencode($batchCode).'/summary'));
    }

    public function finding(string $findingCode): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get('findings/'.rawurlencode($findingCode)));
    }

    private function http(): PendingRequest
    {
        $cfg = config('services.ai_service');
        $http = Http::baseUrl(rtrim($cfg['url'], '/').'/api/v1/task-a/')
            ->acceptJson()
            ->asJson()
            ->timeout((int) ($cfg['timeout'] ?? 30));

        return filled($cfg['key'] ?? null)
            ? $http->withHeaders(['X-Service-Key' => $cfg['key']])
            : $http;
    }

    private function send(callable $call, bool $notFoundIsJobLost = false): array
    {
        try {
            $response = $call($this->http());
        } catch (ConnectionException $e) {
            throw new AiServiceException('Tidak dapat terhubung ke AI service: '.class_basename($e));
        }

        return $this->handle($response, $notFoundIsJobLost);
    }

    private function handle(Response $response, bool $notFoundIsJobLost): array
    {
        if ($response->successful()) {
            return (array) $response->json();
        }

        $body = (array) $response->json();
        $code = $body['error'] ?? null;
        $status = $response->status();

        return match (true) {
            $status === 422 && $code === 'invalid_batch' => throw new AiServiceRejectedException('AI service menolak batch.', $status, $body),
            $status === 409 && $code === 'batch_busy' => throw new AiServiceBusyException('Batch sedang diproses.', $status, $body),
            $status === 404 && $notFoundIsJobLost => throw new AiServiceJobLostException('Job tidak ditemukan di AI service.', $status, $body),
            $status === 401 => throw new AiServiceException('AI service menolak kunci layanan (periksa AI_SERVICE_KEY).', $status, $body),
            default => throw new AiServiceException("AI service mengembalikan HTTP {$status}".($code ? " ({$code})" : '').'.', $status, $body),
        };
    }
}

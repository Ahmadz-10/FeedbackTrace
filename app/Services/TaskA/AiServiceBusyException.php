<?php
namespace App\Services\TaskA;
class AiServiceBusyException extends AiServiceException
{
    public function jobId(): ?string { return $this->body['job_id'] ?? null; }
    public function isRetryable(): bool { return false; }
}

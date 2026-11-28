<?php
namespace App\Services\TaskA;
class AiServiceRejectedException extends AiServiceException
{
    public function errors(): array { return $this->body['errors'] ?? []; }
    public function isRetryable(): bool { return false; }
}

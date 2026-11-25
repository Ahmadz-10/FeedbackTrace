<?php

namespace App\Services\TaskA;

use RuntimeException;

class AiServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly array $body = []
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}

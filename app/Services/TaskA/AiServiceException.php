<?php

namespace App\Services\TaskA;

use RuntimeException;

/** Kegagalan koneksi atau respons AI service. Jaringan dan 5xx dapat dicoba ulang. */
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
        return $this->status === null || $this->status >= 500;
    }
}

<?php
namespace App\Services\TaskA;
class AiServiceJobLostException extends AiServiceException
{
    public function isRetryable(): bool { return false; }
}

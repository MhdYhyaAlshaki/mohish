<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        private readonly string $apiCode,
        string $message,
        private readonly int $status = 400,
        private readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message);
    }

    public function apiCode(): string
    {
        return $this->apiCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}

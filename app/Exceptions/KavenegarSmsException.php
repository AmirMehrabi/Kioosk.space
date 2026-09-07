<?php

namespace App\Exceptions;

use RuntimeException;

class KavenegarSmsException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $providerStatus = null,
    ) {
        parent::__construct($message);
    }

    public function providerStatus(): ?int
    {
        return $this->providerStatus;
    }
}

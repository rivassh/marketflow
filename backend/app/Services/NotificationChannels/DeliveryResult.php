<?php

namespace App\Services\NotificationChannels;

class DeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $failureReason = null,
        public readonly int $attempts = 1
    ) {}

    public static function success(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId);
    }

    public static function failure(string $failureReason, int $attempts = 1): self
    {
        return new self(false, null, $failureReason, $attempts);
    }
}
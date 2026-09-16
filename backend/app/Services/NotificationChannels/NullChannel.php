<?php

namespace App\Services\NotificationChannels;

class NullChannel implements NotificationChannel
{
    public function send(string $recipient, string $message): DeliveryResult
    {
        return DeliveryResult::success(null);
    }

    public function getName(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
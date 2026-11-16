<?php

namespace App\Services\NotificationChannels;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $message): DeliveryResult;

    public function getName(): string;

    public function isConfigured(): bool;
}

<?php

namespace App\Services\NotificationChannels;

class FakeSmsChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message): DeliveryResult
    {
        return DeliveryResult::success('fake-message-id-' . uniqid());
    }

    public function getName(): string
    {
        return 'fake_sms';
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
<?php

namespace App\Services\NotificationChannels;

use Illuminate\Notifications\Notification as LaravelNotification;

class LaravelSmsChannel extends LaravelNotification
{
    protected string $recipient;

    protected string $message;

    public function __construct(string $recipient, string $message)
    {
        $this->recipient = $recipient;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sms',
            'recipient' => $this->recipient,
            'message' => $this->message,
            'sent_at' => now(),
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return [
            'channel' => 'sms',
            'recipient' => $this->recipient,
            'message' => $this->message,
        ];
    }
}

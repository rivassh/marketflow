<?php

namespace App\Services;

use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Notifications\Messages\MailMessage;

class NotificationMessage extends LaravelNotification
{
    protected string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return ['message' => $this->message, 'timestamp' => now()];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->message,
            'timestamp' => now(),
        ];
    }
}
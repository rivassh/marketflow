<?php

namespace App\Services;

use Illuminate\Support\Facades\Notification;

abstract class NotificationService
{
    protected static function send(string $userId, string $message): void
    {
        Notification::route('user', $userId)->notify(new NotificationMessage($message));
    }
}
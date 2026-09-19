<?php

namespace App\Services;

use App\Services\NotificationChannels\DeliveryResult;
use App\Services\NotificationChannels\NotificationChannelInterface as NotificationChannel;
use App\Services\NotificationChannels\ChannelRegistry;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected ChannelRegistry $channelRegistry;

    public function __construct(ChannelRegistry $channelRegistry)
    {
        $this->channelRegistry = $channelRegistry;
    }

    /**
     * Send a notification via configured channels
     */
    public function send(string $userId, string $recipient, string $message, string $template = null): void
    {
        // Log notification for audit trail
        Log::info('Preparing to send notification', [
            'user_id' => $userId,
            'recipient' => $recipient,
            'message' => $message,
            'template' => $template,
        ]);

        // Create notification record
        $notification = Notification::create([
            'user_id' => $userId,
            'recipient' => $recipient,
            'template' => $template,
            'channel' => 'sms',
            'payload' => [
                'message' => $message,
            ],
        ]);

        // Try sending via configured channels
        $channels = $this->channelRegistry->getConfiguredChannels();
        
        if (empty($channels)) {
            $notification->markAsFailed('No notification channels configured');
            Log::warning('No notification channels configured', ['notification_id' => $notification->id]);
            return;
        }

        $success = false;
        foreach ($channels as $channel) {
            try {
                $result = $channel->send($recipient, $message);
                
                if ($result->success) {
                    $notification->markAsSent($result->providerMessageId);
                    $success = true;
                    Log::info('Notification sent successfully', [
                        'notification_id' => $notification->id,
                        'channel' => $channel->getName(),
                        'provider_message_id' => $result->providerMessageId,
                    ]);
                    break;
                } else {
                    Log::warning('Notification failed via channel', [
                        'notification_id' => $notification->id,
                        'channel' => $channel->getName(),
                        'failure_reason' => $result->failureReason,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception sending notification via channel', [
                    'notification_id' => $notification->id,
                    'channel' => $channel->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$success) {
            $notification->markAsFailed('All notification channels failed');
            Log::error('All notification channels failed', [
                'notification_id' => $notification->id,
                'recipient' => $recipient,
            ]);
        }
    }

    /**
     * Send notification using legacy Laravel notification system (for database/web)
     */
    public function sendLegacy(string $userId, string $message): void
    {
        // This maintains backward compatibility for database notifications
        \Illuminate\Support\Facades\Notification::route('user', $userId)
            ->notify(new \App\Services\NotificationMessage($message));
    }
}
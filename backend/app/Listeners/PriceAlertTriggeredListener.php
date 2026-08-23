<?php

namespace App\Listeners;

use App\Events\PriceAlertTriggered;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class PriceAlertTriggeredListener
{
    /**
     * Create the listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PriceAlertTriggered $event)
    {
        // Log the price alert event
        Log::info('Price alert triggered', [
            'alert_id' => $event->alert->id,
            'product_id' => $event->alert->product_id,
            'message' => $event->message,
            'timestamp' => now(),
        ]);

        // Broadcast the event
        Broadcast::to('price-alerts.' . $event->alert->id)
            ->event(new PriceAlertTriggered($event->alert, $event->message));

        // Optionally: Send SMS notification
        // $this->sendSmsNotification($event);
    }
}
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $alert;

    public $message;

    public function __construct($alert, $message)
    {
        $this->alert = $alert;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Private channel that requires authentication
        return new PrivateChannel('price-alerts.'.$this->alert->id);
    }

    /**
     * Broadcast the event data format.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->alert->id,
            'product_id' => $this->alert->product_id,
            'min_price' => $this->alert->min_price,
            'max_price' => $this->alert->max_price,
            'currency' => $this->alert->currency,
            'current_price' => $this->alert->product->last_known_price ?? 0,
            'message' => $this->message,
            'timestamp' => now(),
        ];
    }
}

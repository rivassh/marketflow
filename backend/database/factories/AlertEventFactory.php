<?php

namespace Database\Factories;

use App\Models\AlertEvent;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlertEvent>
 */
class AlertEventFactory extends Factory
{
    protected $model = AlertEvent::class;

    public function definition(): array
    {
        return [
            'watch_id' => Watch::factory()->create()->id,
            'event_key' => hash('sha256', uniqid()),
            'state' => 'condition_met',
            'triggered_at' => now(),
            'resolved_at' => null,
            'payload' => [],
        ];
    }
}

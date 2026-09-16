<?php

namespace Database\Factories;

use App\Models\AlertEvent;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertEvent>
 */
class AlertEventFactory extends Factory
{
    protected $model = AlertEvent::class;

    public function definition(): array
    {
        return [
            'watch_id' => Watch::factory()->create()->id,
            'workflow_id' => null,
            'observation_id' => null,
            'event_key' => sha256(uniqid()),
            'state' => 'condition_met',
            'triggered_at' => now(),
            'resolved_at' => null,
            'payload' => [],
        ];
    }
}
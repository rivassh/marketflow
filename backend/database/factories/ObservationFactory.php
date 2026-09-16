<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    protected $model = Observation::class;

    public function definition(): array
    {
        return [
            'watch_id' => Watch::factory(),
            'provider' => 'torob',
            'observed_at' => now(),
            'normalized_data' => [
                'title' => fake()->words(3, true),
                'price' => ['amount' => fake()->numberBetween(5000000, 15000000), 'currency' => 'IRR'],
                'availability' => fake()->randomElement(['available', 'unavailable']),
            ],
            'raw_data' => ['raw' => 'data'],
            'content_hash' => md5(json_encode(['data'])),
            'status' => 'success',
        ];
    }

    public function error(string $errorCode = 'Exception', string $errorMessage = 'Test error'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'response_time_ms' => 0,
        ]);
    }
}

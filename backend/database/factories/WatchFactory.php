<?php

namespace Database\Factories;

use App\Models\Watch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Watch>
 */
class WatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @return string
     */
    protected $model = Watch::class;

    /**
     * Define how you want the model instances to look.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory()->create()->id,
            'product_url' => 'https://torob.com/p/'.rand(1000,9999).'/product-name-review',
            'product_ref' => ['provider' => 'torob', 'externalId' => (string) rand(1000,9999)],
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [
                [
                    'operator' => 'less_than',
                    'value' => 'price.amount',
                    'expected_value' => 10000000,
                ]
            ],
            'frequency_minutes' => 15,
            'is_active' => true,
            'is_tracked' => true,
            'last_checked_at' => now(),
        ];
    }
}

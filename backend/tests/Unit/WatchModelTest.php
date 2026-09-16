<?php

namespace Tests\Unit;

use App\Models\Watch;
use App\Workflow\WorkflowType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_check_returns_false_when_not_active(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => false,
            'is_tracked' => true,
        ]);

        $this->assertFalse($watch->shouldCheck());
    }

    public function test_should_check_returns_false_when_not_tracked(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => true,
            'is_tracked' => false,
        ]);

        $this->assertFalse($watch->shouldCheck());
    }

    public function test_should_check_returns_true_when_never_checked(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => true,
            'is_tracked' => true,
            'last_checked_at' => null,
        ]);

        $this->assertTrue($watch->shouldCheck());
    }

    public function test_should_check_returns_true_when_frequency_elapsed(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => true,
            'is_tracked' => true,
            'frequency_minutes' => 15,
            'last_checked_at' => Carbon::now()->subMinutes(20),
        ]);

        $this->assertTrue($watch->shouldCheck());
    }

    public function test_should_check_returns_false_when_frequency_not_elapsed(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => true,
            'is_tracked' => true,
            'frequency_minutes' => 15,
            'last_checked_at' => Carbon::now()->subMinutes(5),
        ]);

        $this->assertFalse($watch->shouldCheck());
    }

    public function test_get_config_returns_conditions_array(): void
    {
        $conditions = [
            ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
        ];

        $watch = Watch::factory()->create([
            'conditions' => $conditions,
        ]);

        $this->assertEquals($conditions, $watch->getConfig());
    }

    public function test_get_config_returns_empty_array_when_null(): void
    {
        $watch = Watch::factory()->create([
            'conditions' => null,
        ]);

        $this->assertEquals([], $watch->getConfig());
    }

    public function test_workflow_type_cast_to_enum(): void
    {
        $watch = Watch::factory()->create([
            'workflow_type' => WorkflowType::PRICE_BELOW_ALERT,
        ]);

        $this->assertInstanceOf(WorkflowType::class, $watch->workflow_type);
        $this->assertEquals(WorkflowType::PRICE_BELOW_ALERT, $watch->workflow_type);
    }

    public function test_conditions_cast_to_array(): void
    {
        $conditions = [
            ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
            ['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available'],
        ];

        $watch = Watch::factory()->create([
            'conditions' => $conditions,
        ]);

        $this->assertIsArray($watch->conditions);
        $this->assertCount(2, $watch->conditions);
    }

    public function test_price_history_cast_to_array(): void
    {
        $history = [[9500000, '2025-01-01T00:00:00Z'], [9400000, '2025-01-02T00:00:00Z']];

        $watch = Watch::factory()->create([
            'price_history' => $history,
        ]);

        $this->assertIsArray($watch->price_history);
        $this->assertCount(2, $watch->price_history);
    }
}
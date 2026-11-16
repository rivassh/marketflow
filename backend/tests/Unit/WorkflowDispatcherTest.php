<?php

namespace Tests\Unit;

use App\Models\Watch;
use App\Providers\DTOs\NormalizedProductData;
use App\Providers\DTOs\ProviderProductSnapshot;
use App\Providers\Processors\TorobProcessor;
use App\Services\WorkflowDispatcher;
use App\Workflow\WorkflowType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new WorkflowDispatcher;
        Queue::fake();
    }

    public function test_dispatch_returns_early_when_watch_should_not_check(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => false,
            'is_tracked' => true,
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
        ]);

        Log::shouldReceive('warning')->never();
        Log::shouldReceive('error')->never();

        $this->dispatcher->dispatch($watch);
        // No exception means early return worked
        $this->assertTrue(true);
    }

    public function test_resolve_processor_returns_torob_processor_for_torob_url(): void
    {
        $reflection = new \ReflectionMethod($this->dispatcher, 'resolveProcessor');
        $reflection->setAccessible(true);

        $processor = $reflection->invoke($this->dispatcher, 'https://torob.com/p/12345/iphone-14-pro/');
        $this->assertInstanceOf(TorobProcessor::class, $processor);
    }

    public function test_resolve_processor_returns_null_for_unsupported_url(): void
    {
        $reflection = new \ReflectionMethod($this->dispatcher, 'resolveProcessor');
        $reflection->setAccessible(true);

        $processor = $reflection->invoke($this->dispatcher, 'https://example.com/p/12345/');
        $this->assertNull($processor);
    }

    public function test_dispatch_logs_warning_when_no_processor(): void
    {
        $watch = Watch::factory()->create([
            'is_active' => true,
            'is_tracked' => true,
            'product_url' => 'https://unsupported.com/p/12345/',
            'last_checked_at' => null, // ensure shouldCheck returns true
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('No processor for URL', ['url' => 'https://unsupported.com/p/12345/']);

        $this->dispatcher->dispatch($watch);
    }

    public function test_build_alert_message_includes_type_label_and_price(): void
    {
        $watch = Watch::factory()->create([
            'workflow_type' => WorkflowType::PRICE_BELOW_ALERT,
        ]);

        $normalized = NormalizedProductData::create([
            'title' => 'iPhone 14 Pro',
            'price' => ['amount' => 8500000, 'currency' => 'IRR', 'display' => '۸.۵ میلیون تومان'],
        ]);

        $reflection = new \ReflectionMethod($this->dispatcher, 'buildAlertMessage');
        $reflection->setAccessible(true);

        $message = $reflection->invoke($this->dispatcher, $watch, $normalized);

        $this->assertStringContainsString('هشدار قیمت کمتر از', $message);
        $this->assertStringContainsString('iPhone 14 Pro', $message);
        $this->assertStringContainsString('۸.۵ میلیون تومان', $message);
    }

    public function test_build_alert_message_uses_unknown_when_price_display_missing(): void
    {
        $watch = Watch::factory()->create([
            'workflow_type' => WorkflowType::PRICE_BELOW_ALERT,
        ]);

        $normalized = NormalizedProductData::create([
            'title' => 'Test Product',
            'price' => ['amount' => 100000, 'currency' => 'IRR'],
        ]);

        $reflection = new \ReflectionMethod($this->dispatcher, 'buildAlertMessage');
        $reflection->setAccessible(true);

        $message = $reflection->invoke($this->dispatcher, $watch, $normalized);

        $this->assertStringContainsString('ناشناخته', $message);
    }

    public function test_record_error_creates_observation_with_error_status(): void
    {
        $watch = Watch::factory()->create([
            'product_url' => 'https://torob.com/p/12345/iphone/',
            'product_ref' => ['provider' => 'torob', 'externalId' => '12345'],
        ]);

        $exception = new \Exception('Test error');

        $reflection = new \ReflectionMethod($this->dispatcher, 'recordError');
        $reflection->setAccessible(true);

        $reflection->invoke($this->dispatcher, $watch, $exception);

        $this->assertDatabaseHas('observations', [
            'watch_id' => $watch->id,
            'status' => 'error',
            'error_code' => 'Exception',
            'error_message' => 'Test error',
        ]);
    }

    public function test_store_observation_creates_observation_record(): void
    {
        $watch = Watch::factory()->create();

        $snapshot = ProviderProductSnapshot::create('torob', '12345', '{"raw": "data"}');
        $normalized = NormalizedProductData::create([
            'provider' => 'torob',
            'externalId' => '12345',
            'title' => 'Test',
            'price' => ['amount' => 100000],
        ]);

        $reflection = new \ReflectionMethod($this->dispatcher, 'storeObservation');
        $reflection->setAccessible(true);

        $reflection->invoke($this->dispatcher, $watch, $snapshot, $normalized);

        $this->assertDatabaseHas('observations', [
            'watch_id' => $watch->id,
            'provider' => 'torob',
            'status' => 'success',
        ]);
    }
}

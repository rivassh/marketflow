<?php

namespace Tests\Unit;

use App\Services\DeduplicationService;
use App\Models\Watch;
use App\Models\AlertEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeduplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeduplicationService();
    }

    public function test_create_alert_event_generates_event_key(): void
    {
        $watch = Watch::factory()->create();
        $event = $this->service->createAlertEvent($watch, 'condition_met', ['price' => 9500000]);

        $this->assertNotEmpty($event->event_key);
        $this->assertEquals('condition_met', $event->state);
    }

    public function test_duplicate_event_key_is_rejected(): void
    {
        $watch = Watch::factory()->create();
        $this->service->createAlertEvent($watch, 'condition_met', ['price' => 9500000]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Duplicate alert event');
        $this->service->createAlertEvent($watch, 'condition_met', ['price' => 9500000]);
    }

    public function test_alert_event_can_transition_to_notification_sent(): void
    {
        $event = AlertEvent::factory()->create(['state' => 'condition_met']);
        $event->transitionTo('notification_pending');
        $event->transitionTo('notification_sent');

        $this->assertEquals('notification_sent', $event->state);
    }

    public function test_alert_event_states_are_exhaustive(): void
    {
        $this->assertEquals([
            'condition_not_met',
            'condition_met',
            'notification_pending',
            'notification_sent',
            'notification_failed',
            'resolved',
        ], AlertEvent::states());
    }
}
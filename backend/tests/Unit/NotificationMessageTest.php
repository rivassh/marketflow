<?php

namespace Tests\Unit;

use App\Services\NotificationMessage;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\TestCase;

class NotificationMessageTest extends TestCase
{
    protected NotificationMessage $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification = new NotificationMessage('Test message');
    }

    public function test_via_returns_database_channel(): void
    {
        $channels = $this->notification->via(new \stdClass());
        $this->assertEquals(['database'], $channels);
    }

    public function test_to_database_returns_message_and_timestamp(): void
    {
        $data = $this->notification->toDatabase(new \stdClass());

        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals('Test message', $data['message']);
    }

    public function test_to_array_returns_message_and_timestamp(): void
    {
        $data = $this->notification->toArray(new \stdClass());

        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertEquals('Test message', $data['message']);
    }

    public function test_constructor_sets_message(): void
    {
        $reflection = new \ReflectionProperty($this->notification, 'message');
        $reflection->setAccessible(true);
        $this->assertEquals('Test message', $reflection->getValue($this->notification));
    }
}
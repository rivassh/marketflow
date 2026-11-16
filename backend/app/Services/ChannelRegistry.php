<?php

namespace App\Services;

use App\Services\NotificationChannels\ConfigurableIranianSmsChannel;
use App\Services\NotificationChannels\FakeSmsChannel;
use App\Services\NotificationChannels\NotificationChannelInterface;
use App\Services\NotificationChannels\NullChannel;

class ChannelRegistry
{
    protected array $channels = [];

    public function __construct(array $channels = [])
    {
        $this->channels = $channels;
    }

    public static function createDefault(): static
    {
        $channels = [];

        // Add fake SMS channel if no real provider is configured
        $configurable = new ConfigurableIranianSmsChannel;
        if ($configurable->isConfigured()) {
            $channels[$configurable->getName()] = $configurable;
        } else {
            $channels['fake_sms'] = new FakeSmsChannel;
        }

        // Add null channel as fallback
        $channels['null'] = new NullChannel;

        return new static($channels);
    }

    public function getConfiguredChannels(): array
    {
        return array_values($this->channels);
    }

    public function getChannel(string $name): ?NotificationChannelInterface
    {
        return $this->channels[$name] ?? null;
    }

    public function hasChannel(string $name): bool
    {
        return isset($this->channels[$name]);
    }
}

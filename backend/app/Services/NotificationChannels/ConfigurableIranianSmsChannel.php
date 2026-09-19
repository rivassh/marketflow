<?php

namespace App\Services\NotificationChannels;

class ConfigurableIranianSmsChannel implements NotificationChannelInterface
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderNumber;

    public function __construct()
    {
        $this->apiUrl = config('services.iransms.api_url', '');
        $this->apiKey = config('services.iransms.api_key', '');
        $this->senderNumber = config('services.iransms.sender_number', '');
    }

    public function send(string $recipient, string $message): DeliveryResult
    {
        if (!$this->isConfigured()) {
            return DeliveryResult::failure('SMS provider not configured');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post($this->apiUrl, [
                    'apikey' => $this->apiKey,
                    'to' => $recipient,
                    'from' => $this->senderNumber,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return DeliveryResult::success($data['messageId'] ?? null);
            }

            return DeliveryResult::failure('SMS API error: ' . $response->status());
        } catch (\Exception $e) {
            return DeliveryResult::failure($e->getMessage());
        }
    }

    public function getName(): string
    {
        return 'iranian_sms';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }
}
<?php

namespace App\Workflow;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

abstract class WorkflowAction
{
    protected WorkflowType $type;

    protected mixed $config;

    protected ?string $error = null;

    public function __construct(WorkflowType $type, mixed $config)
    {
        $this->type = $type;
        $this->config = $config;
    }

    abstract public function execute(mixed $value, mixed $currentValue): bool;

    public function validateConfig(): bool
    {
        return ! empty($this->config);
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    protected function triggerNotification(string $userId, string $recipient, string $message, ?string $template = null): bool
    {
        try {
            $service = app(NotificationService::class);
            $service->send($userId, $recipient, $message, $template);

            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('Workflow notification failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}

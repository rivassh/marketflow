<?php

namespace App\Workflow;

use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

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
        return !empty($this->config);
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    protected function triggerNotification(string $userId, string $message): bool
    {
        try {
            NotificationService::send($userId, $message);
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            Log::error('Workflow notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
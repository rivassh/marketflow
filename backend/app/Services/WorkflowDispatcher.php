<?php

namespace App\Services;

use App\Providers\Processors\TorobProcessor;
use App\Workflow\WorkflowConditionEvaluator;
use App\Workflow\WorkflowType;
use App\Models\Watch;
use App\Models\Observation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class WorkflowDispatcher
{
    protected array $processors = [
        'torob' => TorobProcessor::class,
    ];

    protected WorkflowConditionEvaluator $evaluator;

    public function __construct()
    {
        $this->evaluator = new WorkflowConditionEvaluator();
    }

    public function dispatch(Watch $watch): void
    {
        if (!$watch->shouldCheck()) {
            return;
        }

        $processor = $this->resolveProcessor($watch->product_url);
        if (!$processor) {
            Log::warning('No processor for URL', ['url' => $watch->product_url]);
            return;
        }

        try {
            $reference = $processor->parseProductReference($watch->product_url);
            $snapshot = $processor->fetchProduct($reference);
            $normalized = $processor->normalizeSnapshot($snapshot);

            $this->storeObservation($watch, $snapshot, $normalized);
            $this->evaluateAndAlert($watch, $normalized);
            $this->updateWatch($watch, $normalized);

        } catch (\Exception $e) {
            Log::error('Workflow dispatch failed', [
                'watch_id' => $watch->id,
                'error' => $e->getMessage()
            ]);
            $this->recordError($watch, $e);
        }
    }

    protected function resolveProcessor(string $url): ?object
    {
        foreach ($this->processors as $class) {
            $processor = new $class();
            if ($processor->supports($url)) {
                return $processor;
            }
        }
        return null;
    }

    protected function storeObservation(Watch $watch, $snapshot, $normalized): void
    {
        Observation::create([
            'watch_id' => $watch->id,
            'provider' => $snapshot->provider,
            'observed_at' => now(),
            'normalized_data' => (array) $normalized,
            'raw_data' => json_decode($snapshot->rawData, true),
            'content_hash' => $snapshot->contentHash,
            'status' => 'success',
        ]);
    }

    protected function evaluateAndAlert(Watch $watch, $normalized): void
    {
        $conditions = $watch->getConfig();
        $triggered = $this->evaluator->evaluate($normalized, $conditions);

        if ($triggered) {
            $this->triggerAlert($watch, $normalized);
        }
    }

    protected function triggerAlert(Watch $watch, $normalized): void
    {
        $message = $this->buildAlertMessage($watch, $normalized);
        
        NotificationService::send($watch->user_id, $message);

        $watch->increment('alert_count');
    }

    protected function buildAlertMessage(Watch $watch, $normalized): string
    {
        $type = $watch->workflow_type;
        $typeEnum = $type instanceof WorkflowType ? $type : WorkflowType::tryFrom((string) $type);
        $label = $typeEnum?->label() ?? 'هشدار';
        $priceDisplay = $normalized->price['display'] ?? 'ناشناخته';

        return "{$label} برای {$normalized->title}: {$priceDisplay}";
    }

    protected function updateWatch(Watch $watch, $normalized): void
    {
        $watch->update([
            'last_checked_at' => now(),
            'price_history' => array_merge(
                $watch->price_history ?? [],
                [[$normalized->price['amount'] ?? 0, now()->toIso8601String()]]
            ),
        ]);
    }

    protected function recordError(Watch $watch, \Exception $e): void
    {
        Observation::create([
            'watch_id' => $watch->id,
            'provider' => $watch->product_ref->provider ?? 'unknown',
            'observed_at' => now(),
            'normalized_data' => [],
            'raw_data' => [],
            'content_hash' => '',
            'status' => 'error',
            'error_code' => get_class($e),
            'error_message' => $e->getMessage(),
            'response_time_ms' => 0,
        ]);
    }
}
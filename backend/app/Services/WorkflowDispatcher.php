<?php

namespace App\Services;

use App\Models\Observation;
use App\Models\Watch;
use App\Providers\ProviderRegistry;
use App\Workflow\WorkflowConditionEvaluator;
use App\Workflow\WorkflowType;
use Illuminate\Support\Facades\Log;

class WorkflowDispatcher
{
    protected WorkflowConditionEvaluator $evaluator;

    public function __construct(
        protected NotificationService $notificationService,
        protected ProviderRegistry $registry,
        ?WorkflowConditionEvaluator $evaluator = null
    ) {
        $this->evaluator = $evaluator ?? new WorkflowConditionEvaluator;
    }

    public function dispatch(Watch $watch): void
    {
        if (! $watch->shouldCheck()) {
            return;
        }

        $processor = $this->registry->resolve($watch->product_url);
        if (! $processor) {
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
                'error' => $e->getMessage(),
            ]);
            $this->recordError($watch, $e);
        }
    }

    public function getRegistry(): ProviderRegistry
    {
        return $this->registry;
    }

    protected function resolveProcessor(string $url): ?object
    {
        return $this->registry->resolve($url);
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

        $this->notificationService->send(
            (string) $watch->user_id,
            (string) $watch->user->mobile ?? '',
            $message
        );

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
            'next_check_at' => now()->addMinutes($watch->frequency_minutes),
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
            'provider' => $watch->product_ref['provider'] ?? 'unknown',
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

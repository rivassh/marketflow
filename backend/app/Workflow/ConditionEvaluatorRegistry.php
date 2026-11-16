<?php

namespace App\Workflow;

use App\Providers\DTOs\NormalizedProductData;

class ConditionEvaluatorRegistry
{
    protected array $evaluators = [];

    public function __construct()
    {
        $this->registerDefaultEvaluators();
    }

    public function resolve(ConditionOperator $operator): callable
    {
        return $this->evaluators[$operator->value] ?? fn (mixed $data, string $field, mixed $value) => false;
    }

    public function has(ConditionOperator $operator): bool
    {
        return isset($this->evaluators[$operator->value]);
    }

    public function register(string $operator, callable $evaluator): void
    {
        $this->evaluators[$operator] = $evaluator;
    }

    public function evaluate(ConditionOperator $operator, NormalizedProductData $data, string $field, mixed $value): bool
    {
        if (! $this->has($operator)) {
            return false;
        }

        $evaluator = $this->evaluators[$operator->value];

        return $evaluator($data, $field, $value);
    }

    protected function registerDefaultEvaluators(): void
    {
        $this->evaluators = [
            ConditionOperator::EQUALS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) == $value,
            ConditionOperator::NOT_EQUALS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) != $value,
            ConditionOperator::GREATER_THAN->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_numeric($this->resolveValue($data, $field)) && $this->resolveValue($data, $field) > $value,
            ConditionOperator::GREATER_THAN_OR_EQUAL->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_numeric($this->resolveValue($data, $field)) && $this->resolveValue($data, $field) >= $value,
            ConditionOperator::LESS_THAN->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_numeric($this->resolveValue($data, $field)) && $this->resolveValue($data, $field) < $value,
            ConditionOperator::LESS_THAN_OR_EQUAL->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_numeric($this->resolveValue($data, $field)) && $this->resolveValue($data, $field) <= $value,
            ConditionOperator::BETWEEN->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_numeric($this->resolveValue($data, $field)) && is_array($value) && count($value) === 2 && $this->resolveValue($data, $field) >= $value[0] && $this->resolveValue($data, $field) <= $value[1],
            ConditionOperator::CONTAINS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_string($this->resolveValue($data, $field)) && str_contains($this->resolveValue($data, $field), (string) $value),
            ConditionOperator::NOT_CONTAINS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_string($this->resolveValue($data, $field)) && ! str_contains($this->resolveValue($data, $field), (string) $value),
            ConditionOperator::STARTS_WITH->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_string($this->resolveValue($data, $field)) && str_starts_with($this->resolveValue($data, $field), (string) $value),
            ConditionOperator::ENDS_WITH->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => is_string($this->resolveValue($data, $field)) && str_ends_with($this->resolveValue($data, $field), (string) $value),
            ConditionOperator::EXISTS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) !== null,
            ConditionOperator::NOT_EXISTS->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) === null,
            ConditionOperator::MATCHES_REGEX->value => function (NormalizedProductData $data, string $field, mixed $value): bool {
                $resolved = $this->resolveValue($data, $field);
                if (! is_string($resolved) || ! is_string($value) || $value === '') {
                    return false;
                }
                // Accept both delimited ('/pattern/i') and bare patterns
                $pattern = $value;
                if (preg_match('/^\/.*\/[imsxu]*$/', $pattern) === 1) {
                    // already delimited — use as-is
                } else {
                    $pattern = '/'.$pattern.'/i';
                }
                $matched = @preg_match($pattern, $resolved);

                return $matched === 1;
            },
            ConditionOperator::CHANGED->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) !== null,
            ConditionOperator::CHANGED_TO->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) == $value,
            ConditionOperator::CHANGED_FROM->value => fn (NormalizedProductData $data, string $field, mixed $value): bool => $this->resolveValue($data, $field) != $value,
        ];
    }

    protected function resolveValue(NormalizedProductData $data, string $field): mixed
    {
        $keys = explode('.', $field);
        $current = $data;

        foreach ($keys as $key) {
            if (is_object($current) && isset($current->$key)) {
                $current = $current->$key;
            } elseif (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }
}

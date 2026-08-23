<?php

namespace App\Workflow;

use App\Providers\DTOs\NormalizedProductData;
use Illuminate\Support\Str;

class WorkflowConditionEvaluator
{
    public function evaluate(NormalizedProductData $data, array $conditions): bool
    {
        $results = [];

        foreach ($conditions as $condition) {
            $result = $this->evaluateCondition($data, $condition);
            $results[] = $result;
        }

        if (empty($conditions)) {
            return true;
        }

        $logicalOps = ['AND', 'OR', 'XOR', 'NOT'];
        $lastOp = end($conditions)['operator'] ?? 'AND';

        if (count($conditions) === 1) {
            return reset($results);
        }

        $aggregate = reset($results);
        $remaining = array_slice($results, 1);

        foreach ($remaining as $result) {
            if ($lastOp === 'AND') {
                $aggregate = $aggregate && $result;
            } elseif ($lastOp === 'OR') {
                $aggregate = $aggregate || $result;
            } elseif ($lastOp === 'XOR') {
                $aggregate = $aggregate !== $result;
            } elseif ($lastOp === 'NOT') {
                $aggregate = !$aggregate;
            }
        }

        return $aggregate;
    }

    protected function evaluateCondition(NormalizedProductData $data, array $condition): bool
    {
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? $condition['json_path'] ?? null;
        $expectedValue = $condition['expected_value'] ?? null;

        return match ($operator) {
            'equals' => $this->equals($data, $value, $expectedValue),
            'not_equals' => !$this->equals($data, $value, $expectedValue),
            'greater_than' => $this->greaterThan($data, $value, $expectedValue),
            'less_than' => $this->lessThan($data, $value, $expectedValue),
            'between' => $this->between($data, $value, $expectedValue),
            'contains' => $this->contains($data, $value, $expectedValue),
            'matches_regex' => $this->matchesRegex($data, $value, $expectedValue),
            default => false,
        };
    }

    protected function getValue(NormalizedProductData $data, string $path): mixed
    {
        $keys = explode('.', $path);
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

    protected function equals(mixed $data, mixed $path, mixed $expected): bool
    {
        if ($data instanceof NormalizedProductData) {
            $value = $this->getValue($data, $path);
            return $value == $expected || $value === $expected;
        }
        return false;
    }

    protected function greaterThan(NormalizedProductData $data, string $path, mixed $threshold): bool
    {
        $value = $this->getValue($data, $path);
        if (is_numeric($value) && is_numeric($threshold)) {
            return $value > $threshold;
        }
        return false;
    }

    protected function lessThan(NormalizedProductData $data, string $path, mixed $threshold): bool
    {
        $value = $this->getValue($data, $path);
        if (is_numeric($value) && is_numeric($threshold)) {
            return $value < $threshold;
        }
        return false;
    }

    protected function between(NormalizedProductData $data, string $path, array $range): bool
    {
        $value = $this->getValue($data, $path);
        if (is_numeric($value) && is_array($range) && count($range) === 2) {
            return $value >= $range[0] && $value <= $range[1];
        }
        return false;
    }

    protected function contains(NormalizedProductData $data, string $path, string $needle): bool
    {
        $value = $this->getValue($data, $path);
        if (is_string($value)) {
            return Str::contains($value, $needle);
        }
        return false;
    }

    protected function matchesRegex(NormalizedProductData $data, string $path, string $pattern): bool
    {
        $value = $this->getValue($data, $path);
        if (is_string($value)) {
            return (bool) preg_match($pattern, $value);
        }
        return false;
    }
}
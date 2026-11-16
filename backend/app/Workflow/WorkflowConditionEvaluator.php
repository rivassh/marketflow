<?php

namespace App\Workflow;

use App\Providers\DTOs\NormalizedProductData;

class WorkflowConditionEvaluator
{
    protected ConditionEvaluatorRegistry $registry;

    public function __construct(?ConditionEvaluatorRegistry $registry = null)
    {
        $this->registry = $registry ?? new ConditionEvaluatorRegistry;
    }

    public function evaluate(NormalizedProductData $data, array $conditions): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $parsedConditions = array_map(fn ($c) => is_array($c) ? Condition::fromArray($c) : $c, $conditions);

        return $this->evaluateGroup($data, $parsedConditions, 'AND');
    }

    protected function evaluateGroup(NormalizedProductData $data, array $conditions, string $groupOperator): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $results = [];
        $currentOperator = $groupOperator;
        $i = 0;
        $count = count($conditions);

        while ($i < $count) {
            $condition = $conditions[$i];

            // Logical operator acts as a group separator
            if ($condition instanceof Condition && $this->isLogicalOperator($condition->operator)) {
                $currentOperator = strtoupper($condition->operator);
                $i++;

                continue;
            }

            $results[] = $this->evaluateCondition($data, $condition);
            $i++;
        }

        if (empty($results)) {
            return true;
        }

        if (count($results) === 1) {
            return reset($results);
        }

        return $this->aggregateResults($results, $currentOperator);
    }

    protected function evaluateCondition(NormalizedProductData $data, Condition|array $condition): bool
    {
        if (is_array($condition)) {
            $condition = Condition::fromArray($condition);
        }

        $operator = ConditionOperator::tryFrom($condition->operator);
        if (! $operator) {
            return false;
        }

        return $this->registry->evaluate($operator, $data, $condition->field, $condition->value);
    }

    protected function aggregateResults(array $results, string $operator): bool
    {
        return match (strtoupper($operator)) {
            'AND' => ! in_array(false, $results, true),
            'OR' => in_array(true, $results, true),
            'XOR' => count(array_keys($results, true)) % 2 !== 0,
            'NOT' => count($results) === 1 ? ! $results[0] : false,
            default => ! in_array(false, $results, true),
        };
    }

    protected function isLogicalOperator(string $operator): bool
    {
        return in_array(strtoupper($operator), ['AND', 'OR', 'XOR', 'NOT'], true);
    }
}

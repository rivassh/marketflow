<?php

namespace App\Workflow;

class Condition
{
    public function __construct(
        public readonly string $operator,
        public readonly string $field,
        public readonly mixed $value,
        public readonly ?string $logicalOperator = 'AND'
    ) {}

    public static function fromArray(array $data): self
    {
        // Two conventions are supported:
        // 1. {operator, field, value}                 — value is the comparison value
        // 2. {operator, value, expected_value}         — value is the field path
        if (array_key_exists('field', $data)) {
            $field = $data['field'];
            $val = $data['value'] ?? $data['expected_value'] ?? null;
        } elseif (array_key_exists('expected_value', $data)) {
            $field = $data['value'] ?? '';
            $val = $data['expected_value'];
        } else {
            $field = $data['value'] ?? '';
            $val = null;
        }

        return new self(
            operator: $data['operator'] ?? 'equals',
            field: $field,
            value: $val,
            logicalOperator: $data['logical'] ?? $data['logicalOperator'] ?? 'AND'
        );
    }

    public function toArray(): array
    {
        return [
            'operator' => $this->operator,
            'field' => $this->field,
            'value' => $this->value,
            'logical' => $this->logicalOperator,
        ];
    }
}

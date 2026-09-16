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
        return new self(
            operator: $data['operator'] ?? 'equals',
            field: $data['field'] ?? $data['value'] ?? '',
            value: $data['value'] ?? $data['expected_value'] ?? null,
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
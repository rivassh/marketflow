<?php

namespace App\SiteActions;

class SiteActionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $reason = null,
        public readonly array $data = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'reason' => $this->reason,
            'data' => $this->data,
        ];
    }
}

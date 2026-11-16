<?php

namespace App\SiteActions;

/**
 * A human-readable plan describing what an action will do.
 * Returned by SiteAction::plan() before execution so the user can
 * review and approve it (human-in-the-loop).
 */
class SiteActionPlan
{
    public function __construct(
        public readonly string $action,
        public readonly string $url,
        public readonly array $steps,
        public readonly array $inputs = [],
        public readonly bool $requiresApproval = true,
        public readonly array $warnings = [],
        public readonly bool $dryRun = false
    ) {}

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'url' => $this->url,
            'steps' => $this->steps,
            'inputs' => $this->inputs,
            'requires_approval' => $this->requiresApproval,
            'warnings' => $this->warnings,
            'dry_run' => $this->dryRun,
        ];
    }
}

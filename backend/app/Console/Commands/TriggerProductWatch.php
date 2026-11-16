<?php

namespace App\Console\Commands;

use App\Models\Watch;
use App\Services\WorkflowDispatcher;
use Illuminate\Console\Command;

class TriggerProductWatch extends Command
{
    protected $signature = 'trigger:watch {--watch-id= : Specific watch ID to check}';

    protected $description = 'Trigger product watch checks';

    public function handle(): int
    {
        $dispatcher = new WorkflowDispatcher;
        $watchId = $this->option('watch-id');

        if ($watchId) {
            $watch = Watch::find($watchId);
            if (! $watch) {
                $this->error("Watch not found: {$watchId}");

                return Command::FAILURE;
            }
            $dispatcher->dispatch($watch);
            $this->info("Check completed for watch #{$watch->id}");

            return Command::SUCCESS;
        }

        $watches = Watch::where('is_active', true)
            ->where('is_tracked', true)
            ->get();

        $this->info("Found {$watches->count()} active watches to check");

        foreach ($watches as $watch) {
            $this->info("Checking watch #{$watch->id}...");
            $dispatcher->dispatch($watch);
        }

        $this->info('All watches checked successfully');

        return Command::SUCCESS;
    }
}

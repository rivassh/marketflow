<?php

namespace App\Console\Commands;

use App\Models\Watch;
use App\Services\WorkflowDispatcher;
use Illuminate\Console\Command;

class RunProductWatchers extends Command
{
    protected $signature = 'workflow:process
                            {--watch-id= : Specific watch ID to check}
                            {--batch= : Number of watches to process}';

    protected $description = 'Process product watchers for price alerts';

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
            $this->info("Processed watch #{$watch->id}");

            return Command::SUCCESS;
        }

        $query = Watch::where('is_active', true)
            ->where('is_tracked', true);

        $batchSize = (int) $this->option('batch') ?: 50;
        $watches = $query->limit($batchSize)->get();

        if ($watches->isEmpty()) {
            $this->info('No active watches to process');

            return Command::SUCCESS;
        }

        $this->info("Processing {$watches->count()} watches...");

        foreach ($watches as $watch) {
            $this->dispatchWatch($dispatcher, $watch);
        }

        $this->info('All watches processed');

        return Command::SUCCESS;
    }

    protected function dispatchWatch(WorkflowDispatcher $dispatcher, Watch $watch): void
    {
        $this->line("  Checking watch #{$watch->id}...");
        $dispatcher->dispatch($watch);
    }
}

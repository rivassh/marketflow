<?php

namespace App\Console\Commands;

use App\Jobs\CheckWatchJob;
use App\Models\Watch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class MonitorDispatchDue extends Command
{
    protected $signature = 'monitor:dispatch-due
                            {--batch= : Number of watches to dispatch per batch}
                            {--force : Force dispatch regardless of next_check_at}';

    protected $description = 'Dispatch due watches for monitoring';

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch') ?: 50;
        $force = $this->option('force');

        $query = Watch::where('is_active', true)
            ->where('is_tracked', true);

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('next_check_at')
                    ->orWhere('next_check_at', '<', now());
            });
        }

        $watches = $query->limit($batchSize)->get();

        if ($watches->isEmpty()) {
            $this->info('No due watches to dispatch');

            return Command::SUCCESS;
        }

        $this->info("Dispatching {$watches->count()} watches...");

        $jobIds = [];
        foreach ($watches as $watch) {
            try {
                $jobId = Queue::connection()->push(
                    new CheckWatchJob($watch->id)
                );
                $jobIds[] = $jobId;
                $watch->update(['next_check_at' => now()->addMinutes($watch->frequency_minutes)]);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch watch {$watch->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Successfully dispatched '.count($jobIds).' watches');

        return Command::SUCCESS;
    }
}

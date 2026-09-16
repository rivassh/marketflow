<?php

namespace App\Jobs;

use App\Models\Watch;
use App\Services\WorkflowDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CheckWatchJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected ?Watch $watch = null;

    public function __construct(protected readonly int $watchId)
    {
        //
    }

    public function handle(): void
    {
        $watch = Watch::find($this->watchId);

        if (!$watch) {
            Log::error("Watch not found: {$this->watchId}");
            return;
        }

        if (!$watch->shouldCheck()) {
            return;
        }

        $dispatcher = new WorkflowDispatcher();
        $dispatcher->dispatch($watch);
    }

    public function failed(\Exception $exception): void
    {
        Log::error("CheckWatchJob failed for watch {$this->watchId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
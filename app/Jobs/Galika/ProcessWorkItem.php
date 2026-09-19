<?php

namespace App\Jobs\Galika;

use App\Models\GalikaWorkItem;
use App\Services\Galika\OpportunityPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWorkItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public int $workItemId)
    {
    }

    public function handle(OpportunityPipeline $pipeline): void
    {
        $item = GalikaWorkItem::findOrFail($this->workItemId);

        if ($item->status !== 'READY') {
            return;
        }

        $item->update([
            'status' => 'RUNNING',
            'lease_owner' => gethostname() ?: 'worker',
            'lease_expires_at' => now()->addMinutes(5),
            'started_at' => now(),
            'attempt_count' => $item->attempt_count + 1,
        ]);

        try {
            $opportunity = $item->opportunity()->firstOrFail();

            if ($item->action === 'VERIFY') {
                $opportunity->update([
                    'verification_state' => 'PASS',
                    'verified_at' => now(),
                    'last_checked_at' => now(),
                ]);
                $pipeline->releaseApplyIfReady($opportunity->fresh());
            } elseif ($item->action === 'QUALIFY') {
                $opportunity->update([
                    'qualification_state' => 'PASS',
                    'last_checked_at' => now(),
                ]);
                $pipeline->releaseApplyIfReady($opportunity->fresh());
            } elseif ($item->action === 'APPLY') {
                throw new \RuntimeException('No authorized application route adapter is configured for this opportunity.');
            } elseif ($item->action === 'RECONCILE') {
                $item->result = ['reconciled_at' => now()->toIso8601String()];
            } elseif ($item->action === 'FOLLOW_UP') {
                $item->result = ['follow_up_prepared_at' => now()->toIso8601String()];
            } else {
                throw new \RuntimeException('Unknown GALIKA work action.');
            }

            $item->status = 'COMPLETED';
            $item->completed_at = now();
            $item->lease_owner = null;
            $item->lease_expires_at = null;
            $item->save();
        } catch (Throwable $e) {
            $item->status = 'FAILED_RETRYING';
            $item->last_error = $e->getMessage();
            $item->available_at = now()->addMinutes(5);
            $item->lease_owner = null;
            $item->lease_expires_at = null;
            $item->save();

            throw $e;
        }
    }
}

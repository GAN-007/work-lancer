<?php

namespace App\Console\Commands;

use App\Models\GalikaWorkItem;
use Illuminate\Console\Command;

class GalikaWatchdog extends Command
{
    protected $signature = 'galika:watchdog';
    protected $description = 'Recover stale GALIKA work and report operational defects';

    public function handle(): int
    {
        $now = now();

        $expired = GalikaWorkItem::query()
            ->whereIn('status', ['CLAIMED', 'RUNNING'])
            ->whereNotNull('lease_expires_at')
            ->where('lease_expires_at', '<', $now)
            ->get();

        foreach ($expired as $item) {
            $item->update([
                'status' => 'FAILED_RETRYING',
                'lease_owner' => null,
                'lease_expires_at' => null,
                'available_at' => $now,
                'last_error' => 'Watchdog recovered expired work lease.',
            ]);
        }

        $staleReady = GalikaWorkItem::query()
            ->where('status', 'READY')
            ->where('created_at', '<', $now->copy()->subMinutes(15))
            ->count();

        $this->info("Recovered {$expired->count()} expired leases; {$staleReady} stale READY items remain.");

        return self::SUCCESS;
    }
}

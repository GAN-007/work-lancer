<?php

namespace App\Console\Commands;

use App\Jobs\Galika\ProcessWorkItem;
use App\Models\GalikaWorkItem;
use Illuminate\Console\Command;

class GalikaDispatchReady extends Command
{
    protected $signature = 'galika:dispatch-ready {--limit=100}';
    protected $description = 'Dispatch ready GALIKA work items to the queue';

    public function handle(): int
    {
        $items = GalikaWorkItem::query()
            ->where('status', 'READY')
            ->where(function ($query) {
                $query->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($items as $item) {
            ProcessWorkItem::dispatch($item->id)->onQueue(config('galika.queue', 'galika'));
        }

        $this->info("Dispatched {$items->count()} GALIKA work items.");

        return self::SUCCESS;
    }
}

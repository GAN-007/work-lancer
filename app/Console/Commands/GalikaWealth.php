<?php
namespace App\Console\Commands;

use App\Galika\Services\WealthExecutionService;
use App\Models\GalikaWealthItem;
use Illuminate\Console\Command;

class GalikaWealth extends Command
{
    protected $signature='galika:wealth {--limit=20}';
    protected $description='Plan and progress non-job wealth opportunities';

    public function handle(WealthExecutionService $wealth):int
    {
        GalikaWealthItem::where('state','DISCOVERED')->orderBy('priority')->limit((int)$this->option('limit'))->each(fn($i)=>$wealth->plan($i));
        return self::SUCCESS;
    }
}

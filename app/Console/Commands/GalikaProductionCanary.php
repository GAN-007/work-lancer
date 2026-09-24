<?php
namespace App\Console\Commands;

use App\Galika\Services\ProductionCanaryService;
use Illuminate\Console\Command;

class GalikaProductionCanary extends Command
{
    protected $signature='galika:production-canary {user_id} {opportunity_id}';
    protected $description='Execute a controlled real GALIKA application and reconcile Gmail acknowledgement';

    public function handle(ProductionCanaryService $canary):int
    {
        $r=$canary->run((int)$this->argument('user_id'),(int)$this->argument('opportunity_id'));
        $this->line($r->state.' '.$r->run_key);
        return in_array($r->state,['PASSED','WAITING_ACK'],true)?self::SUCCESS:self::FAILURE;
    }
}

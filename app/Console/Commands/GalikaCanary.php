<?php
namespace App\Console\Commands;

use App\Galika\Services\CanaryService;
use Illuminate\Console\Command;

class GalikaCanary extends Command
{
    protected $signature='galika:canary {user_id}';
    protected $description='Run controlled GALIKA end-to-end acceptance checks for a configured user';

    public function handle(CanaryService $canary):int
    {
        $r=$canary->run((int)$this->argument('user_id'));
        $this->line($r->state.' '.$r->run_key);
        return $r->state==='PASSED'?self::SUCCESS:self::FAILURE;
    }
}

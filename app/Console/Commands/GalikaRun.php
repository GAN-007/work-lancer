<?php
namespace App\Console\Commands;

use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\DeliveryReconciliationService;
use App\Galika\Services\DiscoveryService;
use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Console\Command;
use Throwable;

class GalikaRun extends Command{
    protected $signature='galika:run {--limit=25}';
    protected $description='Discover, qualify, apply, reconcile and follow up GALIKA opportunities';
    public function handle(DiscoveryService $d,ApplicationEngine $e,DeliveryReconciliationService $delivery):int{
        $n=$d->discover();$this->info("Discovered {$n} new opportunities");
        GalikaProfile::where('autonomous_apply_enabled',true)->where('pause_all_execution',false)->each(function($profile)use($e){
            GalikaOpportunity::orderByDesc('discovered_at')->limit((int)$this->option('limit'))->each(function($o)use($e,$profile){try{$a=$e->process($o,$profile->user_id);$this->line("{$profile->user_id} | {$o->employer} | {$o->title} => {$a->status}");}catch(Throwable $x){report($x);$this->error($x->getMessage());}});
        });
        GalikaApplication::whereNotNull('route')->whereIn('delivery_state',[null,'SENT_PENDING','PENDING'])->limit(100)->each(fn($a)=>$delivery->reconcile($a));
        return self::SUCCESS;
    }
}

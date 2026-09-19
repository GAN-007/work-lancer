<?php
namespace App\Console\Commands;
use App\Galika\Services\DeliveryReconciliationService;
use App\Galika\Services\DiscoveryService;
use App\Galika\Services\QueueOrchestratorService;
use App\Models\GalikaApplication;
use Illuminate\Console\Command;
class GalikaRun extends Command{
 protected $signature='galika:run {--limit=50}';
 protected $description='Discover, enqueue, reconcile and advance GALIKA opportunities';
 public function handle(DiscoveryService $d,QueueOrchestratorService $orchestrator,DeliveryReconciliationService $delivery):int{
  $n=$d->discover();$q=$orchestrator->enqueueApplications((int)$this->option('limit'));
  $this->info("Discovered {$n}; enqueued {$q} candidate-opportunity pairs");
  GalikaApplication::whereNotNull('route')->whereIn('delivery_state',[null,'SENT_PENDING','PENDING'])->limit(100)->each(fn($a)=>$delivery->reconcile($a));
  return self::SUCCESS;
 }
}

<?php
namespace App\Console\Commands;
use App\Galika\Services\ApplicationEngine;use App\Galika\Services\WorkQueueService;use App\Models\GalikaOpportunity;use Illuminate\Console\Command;
class GalikaWorker extends Command{
 protected $signature='galika:worker {--limit=20}';
 protected $description='Lease and execute GALIKA durable work items';
 public function handle(WorkQueueService $q,ApplicationEngine $engine):int{
  foreach($q->lease((int)$this->option('limit')) as $row){
   try{
    $payload=json_decode($row['payload']??'{}',true)?:[];
    if(($row['kind']??'')==='APPLY'){
      $o=GalikaOpportunity::findOrFail($payload['opportunity_id']);
      $engine->process($o,(int)$payload['user_id']);
    }
    $q->complete((int)$row['id']);
   }catch(\Throwable $e){$q->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));report($e);}
  }return self::SUCCESS;
 }
}

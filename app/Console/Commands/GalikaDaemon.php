<?php
namespace App\Console\Commands;
use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\WorkQueueService;
use App\Models\GalikaOpportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class GalikaDaemon extends Command {
 protected $signature='galika:daemon {--sleep=1} {--lease=50}';
 protected $description='Always-on PostgreSQL-backed immediate application worker';
 public function handle(WorkQueueService $q,ApplicationEngine $engine):int {
  $instance=(string)Str::uuid();
  while(true){
   DB::table('galika_worker_heartbeats')->updateOrInsert(['worker'=>'application'],['instance_id'=>$instance,'state'=>'RUNNING','heartbeat_at'=>now(),'metadata'=>json_encode(['pid'=>getmypid()]),'created_at'=>now(),'updated_at'=>now()]);
   $rows=$q->lease((int)$this->option('lease'),180);
   foreach($rows as $row){try{$p=json_decode($row['payload']??'{}',true)?:[];if(($row['kind']??'')==='APPLY')$engine->process(GalikaOpportunity::findOrFail($p['opportunity_id']),(int)$p['user_id']);$q->complete((int)$row['id']);}catch(\Throwable $e){$q->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));report($e);}}
   if(!$rows)usleep(max(1,(int)$this->option('sleep'))*1000000);
  }
 }
}
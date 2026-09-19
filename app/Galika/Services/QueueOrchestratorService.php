<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;use App\Models\GalikaProfile;
class QueueOrchestratorService{
 public function __construct(private WorkQueueService $queue){}
 public function enqueueApplications(int $limit=100):int{
  $n=0;
  $profiles=GalikaProfile::where('autonomous_apply_enabled',true)->where('pause_all_execution',false)->get();
  $opps=GalikaOpportunity::orderByDesc('discovered_at')->limit($limit)->get();
  foreach($profiles as $p)foreach($opps as $o){
   $key=hash('sha256',"APPLY|{$p->user_id}|{$o->id}");
   $this->queue->enqueue('APPLY',['user_id'=>$p->user_id,'opportunity_id'=>$o->id],$key);
   $n++;
  }return $n;
 }
}

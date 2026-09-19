<?php
namespace App\Console\Commands;
use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\CampaignExecutionService;
use App\Galika\Services\FollowUpExecutionService;
use App\Galika\Services\InterviewOrchestrationService;
use App\Galika\Services\OfferOrchestrationService;
use App\Galika\Services\WealthExecutionService;
use App\Galika\Services\WorkQueueService;
use App\Models\GalikaInterview;
use App\Models\GalikaOffer;
use App\Models\GalikaOpportunity;
use App\Models\GalikaWealthItem;
use Illuminate\Console\Command;
class GalikaWorker extends Command{
 protected $signature='galika:worker {--limit=20}';
 protected $description='Lease and execute GALIKA durable work items';
 public function handle(WorkQueueService $q,ApplicationEngine $engine,InterviewOrchestrationService $interviews,OfferOrchestrationService $offers,WealthExecutionService $wealth):int{
  foreach($q->lease((int)$this->option('limit')) as $row){
   try{
    $payload=json_decode($row['payload']??'{}',true)?:[];$kind=$row['kind']??'';
    if($kind==='APPLY')$engine->process(GalikaOpportunity::findOrFail($payload['opportunity_id']),(int)$payload['user_id']);
    elseif($kind==='INTERVIEW_PREP')$interviews->prepare(GalikaInterview::findOrFail($payload['interview_id']));
    elseif($kind==='OFFER_REVIEW')$offers->analyze(GalikaOffer::findOrFail($payload['offer_id']));
    elseif($kind==='WEALTH_PLAN')$wealth->plan(GalikaWealthItem::findOrFail($payload['wealth_item_id']));
    $q->complete((int)$row['id']);
   }catch(\Throwable $e){$q->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));report($e);}
  }return self::SUCCESS;
 }
}

<?php
namespace App\Console\Commands;

use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\InterviewOrchestrationService;
use App\Galika\Services\OfferOrchestrationService;
use App\Galika\Services\WealthExecutionService;
use App\Galika\Services\WorkQueueService;
use App\Models\GalikaInterview;
use App\Models\GalikaOffer;
use App\Models\GalikaOpportunity;
use App\Models\GalikaWealthItem;
use Illuminate\Console\Command;

class GalikaWorker extends Command
{
    protected $signature='galika:worker {--limit=20}';
    protected $description='Lease and execute GALIKA durable work items';

    public function handle(
        WorkQueueService $queue,
        ApplicationEngine $applications,
        InterviewOrchestrationService $interviews,
        OfferOrchestrationService $offers,
        WealthExecutionService $wealth
    ): int {
        foreach($queue->lease((int)$this->option('limit')) as $row){
            try{
                $payload=json_decode($row['payload']??'{}',true,512,JSON_THROW_ON_ERROR)?:[];
                $kind=(string)($row['kind']??'');

                if($kind==='APPLY'){
                    $application=$applications->process(
                        GalikaOpportunity::findOrFail((int)$payload['opportunity_id']),
                        (int)$payload['user_id']
                    );

                    if($application->status==='FAILED_RETRYING'){
                        $queue->retry(
                            (int)$row['id'],
                            'APPLICATION_RETRY:'.($application->failure_class?:$application->blocker?:'UNKNOWN'),
                            (int)($row['attempts']??1),
                            $application->next_retry_at
                        );
                        continue;
                    }

                    if($application->status==='SUBMITTING'){
                        $queue->retry((int)$row['id'],'APPLICATION_STILL_SUBMITTING',(int)($row['attempts']??1),now()->addMinute());
                        continue;
                    }
                }elseif($kind==='INTERVIEW_PREP'){
                    $interviews->prepare(GalikaInterview::findOrFail((int)$payload['interview_id']));
                }elseif($kind==='OFFER_REVIEW'){
                    $offers->analyze(GalikaOffer::findOrFail((int)$payload['offer_id']));
                }elseif($kind==='WEALTH_PLAN'){
                    $wealth->plan(GalikaWealthItem::findOrFail((int)$payload['wealth_item_id']));
                }else{
                    throw new \RuntimeException('Unsupported GALIKA work kind '.$kind);
                }

                $queue->complete((int)$row['id']);
            }catch(\Throwable $e){
                $queue->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));
                report($e);
            }
        }
        return self::SUCCESS;
    }
}

<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaCanaryRun;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Str;
use RuntimeException;

class ProductionCanaryService
{
    public function __construct(
        private ApplicationEngine $applications,
        private RecruiterThreadService $threads
    ){}

    public function run(int $userId,int $opportunityId):GalikaCanaryRun
    {
        $run=GalikaCanaryRun::create([
            'user_id'=>$userId,
            'run_key'=>(string)Str::uuid(),
            'scenario'=>'CAREER_END_TO_END',
            'state'=>'RUNNING',
            'started_at'=>now(),
            'steps'=>[],
        ]);

        $steps=[];
        try{
            $o=GalikaOpportunity::findOrFail($opportunityId);
            if($o->published_at && $o->published_at->lt(now()->subHours(config('galika.fresh_fallback_hours',24)))){
                throw new RuntimeException('Controlled canary opportunity is older than the 24-hour hard limit.');
            }

            $steps[]=['step'=>'freshness','ok'=>true,'evidence'=>['published_at'=>$o->published_at?->toIso8601String()]];
            $a=$this->applications->process($o,$userId);
            $steps[]=['step'=>'application_execution','ok'=>$a->status==='SUBMITTED_CONFIRMED','evidence'=>[
                'application_id'=>$a->id,'status'=>$a->status,'confirmation_id'=>$a->confirmation_id,'route'=>$a->route,
            ]];

            if($a->status!=='SUBMITTED_CONFIRMED' || empty($a->submission_proof)){
                throw new RuntimeException('Application did not produce explicit submission confirmation evidence.');
            }

            $steps[]=['step'=>'postgres_confirmation','ok'=>true,'evidence'=>[
                'application_id'=>$a->id,
                'confirmation_id'=>$a->confirmation_id,
                'submitted_at'=>$a->submitted_at?->toIso8601String(),
            ]];

            $processed=$this->threads->scan($userId);
            $a->refresh();
            $ack=(bool)$a->last_inbound_at;
            $steps[]=['step'=>'gmail_reconciliation','ok'=>$ack,'evidence'=>[
                'messages_processed'=>$processed,
                'last_inbound_at'=>$a->last_inbound_at?->toIso8601String(),
                'inbound_state'=>$a->inbound_state,
            ]];

            $run->update([
                'state'=>$ack?'PASSED':'WAITING_ACK',
                'steps'=>$steps,
                'evidence'=>[
                    'application_id'=>$a->id,
                    'meaning'=>'CAREER_END_TO_END requires real external submission confirmation plus a subsequently observed Gmail acknowledgement/recruiter message matched to the canonical application.',
                ],
                'finished_at'=>$ack?now():null,
                'failure'=>$ack?null:'Submission is confirmed in PostgreSQL; Gmail acknowledgement has not yet arrived or matched.',
            ]);
        }catch(\Throwable $e){
            $run->update(['state'=>'FAILED','steps'=>$steps,'failure'=>$e->getMessage(),'finished_at'=>now()]);
        }

        return $run->refresh();
    }
}

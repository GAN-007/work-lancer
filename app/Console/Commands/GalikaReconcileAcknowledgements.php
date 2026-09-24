<?php
namespace App\Console\Commands;

use App\Galika\Services\RecruiterThreadService;
use App\Models\GalikaCanaryRun;
use App\Models\GalikaProfile;
use Illuminate\Console\Command;

class GalikaReconcileAcknowledgements extends Command
{
    protected $signature='galika:reconcile-acks';
    protected $description='Reconcile Gmail acknowledgements and close waiting production canaries';

    public function handle(RecruiterThreadService $threads):int
    {
        $processed=0;
        GalikaProfile::where('pause_all_execution',false)->each(function($p)use($threads,&$processed){$processed+=$threads->scan($p->user_id);});

        GalikaCanaryRun::where('scenario','CAREER_END_TO_END')->where('state','WAITING_ACK')->get()->each(function($run){
            $applicationId=data_get($run->evidence,'application_id');
            if(!$applicationId)return;
            $a=\App\Models\GalikaApplication::find($applicationId);
            if($a?->last_inbound_at){
                $steps=$run->steps??[];
                $steps[]=['step'=>'gmail_reconciliation','ok'=>true,'evidence'=>[
                    'last_inbound_at'=>$a->last_inbound_at?->toIso8601String(),
                    'inbound_state'=>$a->inbound_state,
                ]];
                $run->update(['state'=>'PASSED','steps'=>$steps,'failure'=>null,'finished_at'=>now()]);
            }
        });

        $this->info("Processed {$processed} Gmail messages.");
        return self::SUCCESS;
    }
}

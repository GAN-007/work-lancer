<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;

class FollowUpService
{
    public function __construct(private CommunicationCadenceService $cadence){}

    public function schedule(GalikaApplication $a,int $touches=0):void
    {
        if($a->status!=='SUBMITTED_CONFIRMED')return;
        $next=$this->cadence->next($a,$touches);
        $a->update([
            'follow_up_state'=>$next?'SCHEDULED':'PAUSED',
            'follow_up_due'=>$next,
        ]);
    }

    public function due()
    {
        return GalikaApplication::where('status','SUBMITTED_CONFIRMED')
            ->where('follow_up_state','SCHEDULED')
            ->where('follow_up_due','<=',now())
            ->get();
    }
}

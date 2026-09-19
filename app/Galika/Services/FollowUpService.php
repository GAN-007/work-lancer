<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;

class FollowUpService{
    public function schedule(GalikaApplication $a,int $days=5):void{if($a->status!=='SUBMITTED_CONFIRMED')return;$a->update(['follow_up_state'=>'SCHEDULED','follow_up_due'=>now()->addDays($days)]);}
    public function due(){return GalikaApplication::where('status','SUBMITTED_CONFIRMED')->where('follow_up_state','SCHEDULED')->where('follow_up_due','<=',now())->get();}
}

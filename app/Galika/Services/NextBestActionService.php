<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaCampaign;use App\Models\GalikaInterview;use App\Models\GalikaOffer;use App\Models\GalikaNextAction;
class NextBestActionService{
 public function refreshUser(int $userId):int{
  $n=0;
  foreach(GalikaApplication::where('user_id',$userId)->get() as $a){
   $action=null;$priority='NORMAL';$reason=[];
   if($a->status==='BLOCKED_REQUIRES_USER'){$action='RESOLVE_BLOCKER';$priority='HIGH';$reason=['blocker'=>$a->blocker];}
   elseif($a->follow_up_state==='SCHEDULED'&&$a->follow_up_due&&$a->follow_up_due->lte(now())){$action='FOLLOW_UP';$reason=['due'=>$a->follow_up_due];}
   if($action){GalikaNextAction::updateOrCreate(['user_id'=>$userId,'subject_type'=>'APPLICATION','subject_id'=>$a->id,'state'=>'PENDING'],['action'=>$action,'priority'=>$priority,'reason'=>$reason,'due_at'=>now()]);$n++;}
  }
  foreach(GalikaInterview::whereHas('application',fn($q)=>$q->where('user_id',$userId))->whereIn('state',['INVITED','SCHEDULED'])->get() as $i){GalikaNextAction::updateOrCreate(['user_id'=>$userId,'subject_type'=>'INTERVIEW','subject_id'=>$i->id,'state'=>'PENDING'],['action'=>'PREPARE_INTERVIEW','priority'=>'HIGH','reason'=>['stage'=>$i->stage],'due_at'=>$i->starts_at?->copy()->subDay()??now()]);$n++;}
  foreach(GalikaOffer::whereHas('application',fn($q)=>$q->where('user_id',$userId))->where('state','RECEIVED')->get() as $o){GalikaNextAction::updateOrCreate(['user_id'=>$userId,'subject_type'=>'OFFER','subject_id'=>$o->id,'state'=>'PENDING'],['action'=>'REVIEW_OFFER','priority'=>'HIGH','reason'=>['deadline'=>$o->deadline_at],'due_at'=>$o->deadline_at??now()]);$n++;}
  return $n;
 }
}

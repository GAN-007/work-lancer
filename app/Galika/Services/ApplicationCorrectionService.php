<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;
class ApplicationCorrectionService{
 public function __construct(private CareerEventService $events){}
 public function withdraw(GalikaApplication $a,string $reason='USER_REQUEST'):GalikaApplication{
  if(!in_array($a->status,['SUBMITTED_CONFIRMED','VERIFIED','BLOCKED_REQUIRES_USER'],true))return $a;
  $a->update(['correction_state'=>'WITHDRAWN','withdrawn_at'=>now()]);
  $this->events->record($a->user_id,'APPLICATION',$a->id,'WITHDRAWN','WITHDRAWN','USER_CONFIRMED',['reason'=>$reason]);
  return $a->refresh();
 }
 public function supersede(GalikaApplication $old,GalikaApplication $new):void{
  $old->update(['correction_state'=>'SUPERSEDED','superseded_by_application_id'=>$new->id]);
  $this->events->record($old->user_id,'APPLICATION',$old->id,'SUPERSEDED','SUPERSEDED','SYSTEM_VERIFIED',['replacement_application_id'=>$new->id]);
 }
}

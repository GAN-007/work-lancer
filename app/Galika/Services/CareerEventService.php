<?php
namespace App\Galika\Services;
use App\Models\GalikaCareerEvent;
class CareerEventService{
 public function record(int $userId,string $subjectType,int $subjectId,string $eventType,?string $state=null,string $proof='INTERNAL',array $evidence=[]):GalikaCareerEvent{
  return GalikaCareerEvent::create(['user_id'=>$userId,'subject_type'=>$subjectType,'subject_id'=>$subjectId,'event_type'=>$eventType,'state'=>$state,'proof_level'=>$proof,'evidence'=>$evidence,'occurred_at'=>now()]);
 }
}

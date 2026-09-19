<?php
namespace App\Galika\Services;
use App\Models\GalikaOffer;use App\Models\GalikaNextAction;
class OfferOrchestrationService{
 public function analyze(GalikaOffer $o):array{
  $a=$o->application()->with('opportunity')->first();
  $analysis=[
   'status'=>'READY_FOR_REVIEW',
   'base_comp'=>$o->base_comp,'currency'=>$o->currency,'bonus'=>$o->bonus,'equity'=>$o->equity,
   'benefits'=>$o->benefits??[],
   'questions'=>['total_compensation','employment_type','remote_or_relocation_terms','notice_period','benefit_details','offer_deadline'],
   'requires_human'=>true
  ];
  $o->update(['analysis'=>$analysis]);
  GalikaNextAction::updateOrCreate(['user_id'=>$a->user_id,'subject_type'=>'OFFER','subject_id'=>$o->id,'state'=>'PENDING'],['action'=>'REVIEW_AND_NEGOTIATE_OFFER','priority'=>'HIGH','reason'=>['employer'=>$a?->opportunity?->employer],'due_at'=>$o->deadline_at??now()]);
  return $analysis;
 }
}

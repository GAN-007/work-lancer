<?php
namespace App\Galika\Services;

use App\Models\GalikaCampaign;
use App\Models\GalikaEmployer;
use App\Models\GalikaOpportunity;
use App\Models\GalikaRelationship;

class CampaignStrategyService
{
 public function plan(int $userId,GalikaOpportunity $o):GalikaCampaign
 {
  $strategy='ATS';$reasons=[];
  if($o->employer_id){
   $strong=GalikaRelationship::where('user_id',$userId)->whereHas('person',fn($q)=>$q->where('employer_id',$o->employer_id))->whereIn('strength',['STRONG','MEDIUM'])->exists();
   if($strong){$strategy='REFERRAL_FIRST';$reasons[]='KNOWN_RELATIONSHIP';}
   elseif(GalikaEmployer::find($o->employer_id)?->memory){$strategy='RECRUITER_PLUS_ATS';$reasons[]='EMPLOYER_MEMORY';}
  }
  $value=(float)($o->match_score??0)/100;
  return GalikaCampaign::updateOrCreate(['user_id'=>$userId,'opportunity_id'=>$o->id],[
   'employer_id'=>$o->employer_id,'strategy'=>$strategy,'state'=>'ACTIVE',
   'plan'=>['reasons'=>$reasons,'steps'=>$strategy==='REFERRAL_FIRST'?['REQUEST_REFERRAL','APPLY','FOLLOW_UP']:['APPLY','RECRUITER_OUTREACH','FOLLOW_UP']],
   'expected_value'=>$value,'next_action_at'=>now()
  ]);
 }
}

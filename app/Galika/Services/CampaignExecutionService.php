<?php
namespace App\Galika\Services;
use App\Models\GalikaCampaign;
use App\Models\GalikaCampaignAction;
use App\Models\GalikaConversation;
use App\Models\GalikaRelationship;
use App\Models\GalikaProfile;
class CampaignExecutionService{
 public function __construct(private GmailAdapter $gmail,private GalikaIntelligenceService $ai){}
 public function materialize(GalikaCampaign $c):void{
  foreach($c->plan['steps']??[] as $step){
   GalikaCampaignAction::firstOrCreate(['campaign_id'=>$c->id,'action'=>$step],['state'=>'PENDING','scheduled_at'=>now()]);
  }
 }
 public function executeDue(int $limit=50):int{
  $done=0;
  GalikaCampaignAction::where('state','PENDING')->where(fn($q)=>$q->whereNull('scheduled_at')->orWhere('scheduled_at','<=',now()))->limit($limit)->get()->each(function($a)use(&$done){
   try{
    if($a->action==='REQUEST_REFERRAL')$this->referral($a);
    elseif($a->action==='RECRUITER_OUTREACH')$this->recruiter($a);
    elseif($a->action==='FOLLOW_UP'){$a->update(['state'=>'DEFERRED','error'=>'FOLLOW_UP_EXECUTED_BY_FOLLOWUP_WORKER']);return;}
    else{$a->update(['state'=>'READY','proof'=>['reason'=>'handled by application engine']]);return;}
    $done++;
   }catch(\Throwable $e){$a->update(['state'=>'FAILED','error'=>$e->getMessage()]);report($e);}
  });return $done;
 }
 private function referral(GalikaCampaignAction $a):void{
  $c=$a->campaign;$rel=GalikaRelationship::where('user_id',$c->user_id)->whereHas('person',fn($q)=>$q->where('employer_id',$c->employer_id)->whereNotNull('email'))->orderByRaw("CASE strength WHEN 'STRONG' THEN 1 WHEN 'MEDIUM' THEN 2 ELSE 3 END")->with('person')->first();
  if(!$rel){$a->update(['state'=>'FAILED','error'=>'NO_REFERRAL_CONTACT']);return;}
  $opp=\App\Models\GalikaOpportunity::find($c->opportunity_id);$profile=GalikaProfile::firstWhere('user_id',$c->user_id);
  $body="Hi {$rel->person->name},\n\nI'm interested in the {$opp?->title} opportunity at {$opp?->employer}. If you're comfortable, would you be open to referring me or pointing me to the right hiring contact?\n\nThank you.";
  $from=$profile?->email??config('services.gmail.from');if(!$from){$a->update(['state'=>'FAILED','error'=>'FROM_ADDRESS_MISSING']);return;}
  $sent=$this->gmail->send($c->user_id,$from,$rel->person->email,"Referral request — {$opp?->title}",$body);
  GalikaConversation::create(['user_id'=>$c->user_id,'campaign_id'=>$c->id,'channel'=>'EMAIL','direction'=>'OUTBOUND','classification'=>'REFERRAL_REQUEST','body'=>$body,'occurred_at'=>now(),'metadata'=>['person_id'=>$rel->person_id,'message_id'=>$sent['id']??null]]);
  $a->update(['state'=>'EXECUTED','person_id'=>$rel->person_id,'channel'=>'EMAIL','executed_at'=>now(),'proof'=>['message_id'=>$sent['id']??null]]);
 }
 private function recruiter(GalikaCampaignAction $a):void{
  $c=$a->campaign;$opp=\App\Models\GalikaOpportunity::find($c->opportunity_id);
  $rel=GalikaRelationship::where('user_id',$c->user_id)->whereHas('person',fn($q)=>$q->where('employer_id',$c->employer_id)->where('relationship_type','RECRUITER')->whereNotNull('email'))->with('person')->first();
  if(!$rel){$a->update(['state'=>'FAILED','error'=>'NO_VERIFIED_RECRUITER_CONTACT']);return;}
  $profile=GalikaProfile::firstWhere('user_id',$c->user_id);$from=$profile?->email??config('services.gmail.from');
  if(!$from){$a->update(['state'=>'FAILED','error'=>'FROM_ADDRESS_MISSING']);return;}
  $body="Hi {$rel->person->name},\n\nI applied for the {$opp?->title} role at {$opp?->employer}. My background appears well aligned, and I wanted to introduce myself directly. I'd be glad to share any additional information useful for the process.\n\nBest regards";
  $sent=$this->gmail->send($c->user_id,$from,$rel->person->email,"Application — {$opp?->title}",$body);
  GalikaConversation::create(['user_id'=>$c->user_id,'campaign_id'=>$c->id,'channel'=>'EMAIL','direction'=>'OUTBOUND','classification'=>'RECRUITER_OUTREACH','body'=>$body,'occurred_at'=>now(),'metadata'=>['person_id'=>$rel->person_id,'message_id'=>$sent['id']??null]]);
  $a->update(['state'=>'EXECUTED','person_id'=>$rel->person_id,'channel'=>'EMAIL','executed_at'=>now(),'proof'=>['message_id'=>$sent['id']??null]]);
 }
}

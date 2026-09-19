<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaDecision;use App\Models\GalikaOpportunity;use Illuminate\Support\Facades\Http;use RuntimeException;
class ApplicationEngine {
 public function __construct(private GalikaAIService $ai){}
 public function process(GalikaOpportunity $o): GalikaApplication {
  $a=GalikaApplication::firstOrCreate(['opportunity_id'=>$o->id],['application_key'=>hash('sha256',$o->canonical_key),'status'=>'DISCOVERED']); if(in_array($a->status,['SUBMITTED_CONFIRMED','DUPLICATE','INELIGIBLE','STALE','CLOSED'])) return $a;
  if($o->published_at && $o->published_at->lt(now()->subDays(config('galika.max_age_days',30)))){$a->update(['status'=>'STALE']);return $a;}
  $candidate=config('galika.candidate'); $analysis=$this->ai->analyze($candidate,$o->toArray()); $o->update(['verified_at'=>now(),'eligibility'=>$analysis['eligible']?'ELIGIBLE':'INELIGIBLE','match_score'=>$analysis['score'],'evidence'=>array_merge($o->evidence??[],['qualification'=>$analysis])]);
  if(!$analysis['eligible']||$analysis['score']<config('galika.minimum_match_score',70)){$a->update(['status'=>'INELIGIBLE']);return $a;}
  if($analysis['unknown_material_questions']){foreach($analysis['unknown_material_questions'] as $q) GalikaDecision::firstOrCreate(['application_id'=>$a->id,'decision_type'=>'MATERIAL_ANSWER','question'=>$q],['context'=>['opportunity'=>$o->url]]);$a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'UNKNOWN_ANSWER']);return $a;}
  $a->update(['status'=>'VERIFIED','started_at'=>now()]); return $this->submit($a,$o,$analysis['positioning']);
 }
 private function submit(GalikaApplication $a,GalikaOpportunity $o,string $positioning): GalikaApplication {
  $endpoint=config('galika.browser.endpoint');$token=config('galika.browser.token'); if(!$endpoint||!$token){$a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'AUTH']);return $a;}
  $a->increment('attempt_count');$a->update(['status'=>'SUBMITTING']);
  $r=Http::withToken($token)->timeout(120)->post($endpoint,['action'=>'apply_to_job','url'=>$o->url,'candidate'=>config('galika.candidate'),'positioning'=>$positioning,'rules'=>['never_invent_material_answers'=>true,'stop_on_captcha'=>true,'require_submission_confirmation'=>true]]);
  if(!$r->successful()){$code=$r->status()==429?'RATE_LIMIT':($r->status()==401||$r->status()==403?'AUTH':'SITE_ERROR');$a->update(['status'=>'FAILED_RETRYING','blocker'=>$code]);throw new RuntimeException('Browser application failed: '.$r->body());}
  $data=$r->json(); if(!($data['submitted']??false)||empty($data['confirmation'])){$a->update(['status'=>'FAILED_RETRYING','blocker'=>$data['failure_class']??'OTHER']);return $a;}
  $a->update(['status'=>'SUBMITTED_CONFIRMED','route'=>$data['route']??'browser','submitted_at'=>now(),'confirmation_at'=>now(),'confirmation_id'=>$data['confirmation_id']??null,'submission_proof'=>json_encode($data['confirmation']),'discovery_to_submit_sec'=>max(0,now()->diffInSeconds($o->discovered_at))]); return $a;
 }
}

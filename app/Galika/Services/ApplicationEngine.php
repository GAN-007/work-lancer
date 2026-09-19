<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaDecision;
use App\Models\GalikaEvidence;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ApplicationEngine{
    public function __construct(
        private GalikaAIService $ai,
        private PolicyEngine $policies,
        private PlatformCircuitBreaker $circuit,
        private TinyFishAdapter $tinyfish,
        private FollowUpService $followUp
    ){}
    public function process(GalikaOpportunity $o,int $userId):GalikaApplication{
        $a=GalikaApplication::firstOrCreate(['user_id'=>$userId,'opportunity_id'=>$o->id],['application_key'=>hash('sha256',$userId.'|'.$o->canonical_key),'status'=>'DISCOVERED']);
        if(in_array($a->status,['SUBMITTED_CONFIRMED','DUPLICATE','INELIGIBLE','STALE','CLOSED'],true))return $a;
        $profile=GalikaProfile::firstWhere('user_id',$userId);
        if(!$profile||$profile->pause_all_execution||!$profile->autonomous_apply_enabled){$a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'AUTH']);return $a;}
        if($o->published_at&&$o->published_at->lt(now()->subDays(config('galika.max_age_days',30)))){$a->update(['status'=>'STALE','failure_class'=>'CLOSED']);return $a;}
        $policy=$this->policies->evaluate($userId,$o->toArray());if(!$policy['allowed']){$a->update(['status'=>'INELIGIBLE','blocker'=>$policy['reason']]);return $a;}
        $candidate=$this->candidate($userId,$profile);$analysis=$this->ai->analyze($candidate,$o->toArray());
        $o->update(['verified_at'=>now(),'eligibility'=>$analysis['eligible']?'ELIGIBLE':'INELIGIBLE','match_score'=>$analysis['score'],'evidence'=>array_merge($o->evidence??[],['qualification'=>$analysis])]);
        if(!$analysis['eligible']||$analysis['score']<$profile->minimum_match_score){$a->update(['status'=>'INELIGIBLE']);return $a;}
        if($analysis['unknown_material_questions']){foreach($analysis['unknown_material_questions'] as $q)GalikaDecision::firstOrCreate(['application_id'=>$a->id,'decision_type'=>'MATERIAL_ANSWER','question'=>$q],['context'=>['opportunity'=>$o->url],'status'=>'OPEN']);$a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'UNKNOWN_ANSWER','failure_class'=>'UNKNOWN_ANSWER']);return $a;}
        if(!$this->circuit->available('tinyfish')){$a->update(['status'=>'FAILED_RETRYING','blocker'=>'RATE_LIMIT','failure_class'=>'RATE_LIMIT','next_retry_at'=>now()->addMinutes(10)]);return $a;}
        $a->update(['status'=>'VERIFIED','started_at'=>$a->started_at?:now()]);return $this->submit($a,$o,$candidate);
    }
    private function submit(GalikaApplication $a,GalikaOpportunity $o,array $candidate):GalikaApplication{
        if($a->attempt_count>=$a->max_attempts){$a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'OTHER']);return $a;}
        $a->increment('attempt_count');$a->update(['status'=>'SUBMITTING']);
        try{$data=$this->tinyfish->apply($a,$o,$candidate,[]);
            if(!($data['submitted']??false)||empty($data['confirmation'])){$failure=$data['failure_class']??'OTHER';$a->update(['status'=>in_array($failure,['CAPTCHA','UNKNOWN_ANSWER','CV_UPLOAD'],true)?'BLOCKED_REQUIRES_USER':'FAILED_RETRYING','blocker'=>$failure,'failure_class'=>$failure,'next_retry_at'=>now()->addMinutes(5)]);return $a;}
            DB::transaction(function()use($a,$o,$data){$a->update(['status'=>'SUBMITTED_CONFIRMED','route'=>$data['route']??'tinyfish','submitted_at'=>now(),'confirmation_at'=>now(),'confirmation_id'=>$data['confirmation_id']??null,'submission_proof'=>json_encode($data['confirmation']),'discovery_to_submit_sec'=>max(0,now()->diffInSeconds($o->discovered_at)),'failure_class'=>null,'next_retry_at'=>null]);});
            $this->circuit->success('tinyfish');$this->followUp->schedule($a);return $a->refresh();
        }catch(Throwable $e){$this->circuit->failure('tinyfish',$e->getMessage());$a->update(['status'=>'FAILED_RETRYING','blocker'=>'SITE_ERROR','failure_class'=>'SITE_ERROR','next_retry_at'=>now()->addMinutes(min(60,2**min(6,$a->attempt_count)))]);report($e);return $a;}
    }
    private function candidate(int $userId,GalikaProfile $p):array{return ['profile'=>$p->toArray(),'evidence'=>GalikaEvidence::where('user_id',$userId)->where('verified',true)->orderByDesc('confidence')->get(['domain','fact','source_type','source_url','confidence','tags'])->toArray()];}
}

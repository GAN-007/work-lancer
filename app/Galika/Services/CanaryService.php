<?php
namespace App\Galika\Services;

use App\Models\GalikaCanaryRun;
use App\Models\GalikaConnection;
use App\Models\GalikaDocument;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Support\Str;
use Throwable;

class CanaryService
{
    public function __construct(
        private CanonicalizationService $canonical,
        private AtsRouter $ats,
        private CampaignStrategyService $campaigns,
        private OpportunityTrustService $trust,
        private LocationFeasibilityService $location
    ){}

    public function run(int $userId):GalikaCanaryRun
    {
        $run=GalikaCanaryRun::create([
            'user_id'=>$userId,'run_key'=>(string)Str::uuid(),'scenario'=>'PIPELINE_READINESS',
            'state'=>'RUNNING','started_at'=>now(),'steps'=>[]
        ]);

        $steps=[];
        try{
            $profile=GalikaProfile::firstWhere('user_id',$userId);
            $steps[]=$this->step('profile',(bool)$profile);
            $steps[]=$this->step('autonomy',(bool)($profile?->autonomous_apply_enabled)&&!(bool)($profile?->pause_all_execution));

            $verifiedDocs=GalikaDocument::where('user_id',$userId)->where('confirmed',true)->count();
            $steps[]=$this->step('verified_documents',$verifiedDocs>0,['count'=>$verifiedDocs]);

            $connections=GalikaConnection::where('user_id',$userId)->get();
            $steps[]=$this->step('connections',$connections->isNotEmpty(),[
                'providers'=>$connections->pluck('provider')->all(),
                'healthy'=>$connections->where('health','HEALTHY')->pluck('provider')->all(),
            ]);

            $o=GalikaOpportunity::orderByDesc('discovered_at')->first();
            $steps[]=$this->step('opportunity_available',(bool)$o);

            if($o){
                $fp=$this->canonical->fingerprint($o->toArray());
                $steps[]=$this->step('canonicalization',hash_equals((string)$o->fingerprint,$fp)||empty($o->fingerprint),['fingerprint'=>$fp]);

                $trust=$this->trust->assess($o);
                $steps[]=$this->step('trust_gate',$trust['state']!=='HIGH_RISK',$trust);

                $geo=$this->location->evaluate($userId,$o->toArray());
                $steps[]=$this->step('location_gate',$geo['feasible'],$geo);

                $ats=$this->ats->detect($o);
                $steps[]=$this->step('route_detection',(bool)$ats,['ats'=>$ats]);

                $campaign=$this->campaigns->plan($userId,$o);
                $steps[]=$this->step('campaign_strategy',(bool)$campaign,['strategy'=>$campaign->strategy]);

                $duplicate=\App\Models\GalikaApplication::where(['user_id'=>$userId,'opportunity_id'=>$o->id])->count()<=1;
                $steps[]=$this->step('duplicate_protection',$duplicate);
            }

            $hardFailure=collect($steps)->contains(fn($s)=>!$s['ok']&&in_array($s['step'],['profile','autonomy','verified_documents','connections','opportunity_available'],true));
            $run->update([
                'state'=>$hardFailure?'FAILED':'PASSED',
                'steps'=>$steps,
                'evidence'=>['checked_at'=>now()->toIso8601String(),'meaning'=>'Pipeline readiness only. A live CAREER_END_TO_END canary must perform an actual external submission, confirmation, delivery/inbound proof and should never be inferred from this readiness result.'],
                'finished_at'=>now(),
                'failure'=>$hardFailure?'One or more mandatory readiness gates failed':null,
            ]);
        }catch(Throwable $e){
            $run->update(['state'=>'FAILED','steps'=>$steps,'failure'=>$e->getMessage(),'finished_at'=>now()]);
        }
        return $run->refresh();
    }

    private function step(string $step,bool $ok,array $evidence=[]):array
    {
        return ['step'=>$step,'ok'=>$ok,'evidence'=>$evidence];
    }
}

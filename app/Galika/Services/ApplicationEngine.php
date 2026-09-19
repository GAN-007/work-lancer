<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaApplicationAnswer;
use App\Models\GalikaDecision;
use App\Models\GalikaEvidence;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApplicationEngine
{
    public function __construct(
        private GalikaAIService $ai,
        private PolicyEngine $policies,
        private PlatformCircuitBreaker $circuit,
        private TinyFishAdapter $tinyfish,
        private FollowUpService $followUp,
        private DocumentGenerationService $documents,
        private AtsRouter $ats,
        private OpportunityTrustService $trust,
        private LocationFeasibilityService $location,
        private CampaignStrategyService $campaigns,
        private AnswerKnowledgeService $knowledge,
        private PersonaSelectionService $personas,
        private PrivacyDisclosureService $privacy,
        private HumanAssistService $assist,
        private CampaignExecutionService $campaignExec,
        private SourceMetricService $sourceMetrics,
        private EmployerMemoryService $employerMemory
    ){}

    public function process(GalikaOpportunity $o,int $userId):GalikaApplication
    {
        $a=GalikaApplication::firstOrCreate(
            ['user_id'=>$userId,'opportunity_id'=>$o->id],
            ['application_key'=>hash('sha256',$userId.'|'.$o->canonical_key),'status'=>'DISCOVERED']
        );

        if(in_array($a->status,['SUBMITTED_CONFIRMED','DUPLICATE','INELIGIBLE','STALE','CLOSED'],true)) return $a;

        $profile=GalikaProfile::firstWhere('user_id',$userId);
        if(!$profile||$profile->pause_all_execution||!$profile->autonomous_apply_enabled){
            $a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'AUTH']);
            return $a;
        }

        if($o->published_at&&$o->published_at->lt(now()->subDays(config('galika.max_age_days',30)))){
            $a->update(['status'=>'STALE','failure_class'=>'CLOSED']);
            return $a;
        }

        $trust=$this->trust->assess($o);
        if($trust['state']==='HIGH_RISK'){
            $a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'TRUST_REVIEW','failure_class'=>'OTHER']);
            return $a;
        }

        $geo=$this->location->evaluate($userId,$o->toArray());
        if(!$geo['feasible']){
            $a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>$geo['reason'],'failure_class'=>'LOCATION']);
            return $a;
        }

        $policy=$this->policies->evaluate($userId,$o->toArray());
        if(!$policy['allowed']){
            $a->update(['status'=>'INELIGIBLE','blocker'=>$policy['reason']]);
            return $a;
        }

        $persona=$this->personas->select($userId,$o);
        if($persona)$a->update(['persona_id'=>$persona->id]);
        $candidate=$this->candidate($userId,$profile);
        $analysis=$this->ai->analyze($candidate,$o->toArray());
        $o->update([
            'verified_at'=>now(),
            'reverified_at'=>now(),
            'eligibility'=>$analysis['eligible']?'ELIGIBLE':'INELIGIBLE',
            'match_score'=>$analysis['score'],
            'expected_value'=>((float)$analysis['score'])/100,
            'evidence'=>array_merge($o->evidence??[],['qualification'=>$analysis,'location'=>$geo]),
        ]);

        if(!$analysis['eligible']||$analysis['score']<$profile->minimum_match_score){
            $a->update(['status'=>'INELIGIBLE']);
            return $a;
        }

        $unresolved=[];
        foreach($analysis['unknown_material_questions'] as $q){
            $known=$this->knowledge->answer($userId,$q);
            if($known!==null){
                GalikaApplicationAnswer::updateOrCreate(
                    ['application_id'=>$a->id,'question'=>$q],
                    ['answer'=>$known,'source_basis'=>'ANSWER_KNOWLEDGE','humanized'=>$known,'submitted'=>false]
                );
            }else{
                $unresolved[]=$q;
                GalikaDecision::firstOrCreate(
                    ['application_id'=>$a->id,'decision_type'=>'MATERIAL_ANSWER','question'=>$q],
                    ['context'=>['opportunity'=>$o->url,'intent'=>$this->knowledge->intent($q)],'status'=>'OPEN']
                );
            }
        }
        if($unresolved){
            $a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'UNKNOWN_ANSWER','failure_class'=>'UNKNOWN_ANSWER']);
            return $a;
        }

        $campaign=$this->campaigns->plan($userId,$o);
        $this->campaignExec->materialize($campaign);

        if(!$this->circuit->available('tinyfish')){
            $a->update(['status'=>'FAILED_RETRYING','blocker'=>'RATE_LIMIT','failure_class'=>'RATE_LIMIT','next_retry_at'=>now()->addMinutes(10)]);
            return $a;
        }

        $a->update([
            'status'=>'VERIFIED',
            'started_at'=>$a->started_at?:now(),
            'ats_type'=>$this->ats->detect($o),
            'ats_requisition_id'=>$o->requisition_id,
            'submission_state_detail'=>'READY_TO_SUBMIT'
        ]);

        return $this->submit($a,$o,$candidate);
    }

    private function submit(GalikaApplication $a,GalikaOpportunity $o,array $candidate):GalikaApplication
    {
        if($a->attempt_count>=$a->max_attempts){
            $a->update(['status'=>'BLOCKED_REQUIRES_USER','blocker'=>'OTHER']);
            return $a;
        }

        $a->increment('attempt_count');
        $a->update(['status'=>'SUBMITTING','submission_state_detail'=>'SUBMISSION_ATTEMPTED']);

        try{
            $pack=$this->documents->generateForOpportunity($a->user_id,$o);
            $attachments=[
                ['kind'=>'resume','path'=>Storage::disk($pack['resume']->disk)->path($pack['resume']->path),'sha256'=>$pack['resume']->sha256],
                ['kind'=>'cover_letter','path'=>Storage::disk($pack['cover_letter']->disk)->path($pack['cover_letter']->path),'sha256'=>$pack['cover_letter']->sha256],
            ];

            $answers=GalikaApplicationAnswer::where('application_id',$a->id)->get()
                ->mapWithKeys(fn($x)=>[$x->question=>$x->answer])->all();

            $candidate=$this->privacy->filter($a->user_id,$candidate,'APPLICATION')+['profile'=>$candidate['profile']??[],'evidence'=>$candidate['evidence']??[]];
            $data=$this->tinyfish->apply($a,$o,$candidate,$answers,$attachments);

            if(!($data['submitted']??false)||empty($data['confirmation'])){
                $failure=$data['failure_class']??'OTHER';
                $a->update([
                    'status'=>in_array($failure,['CAPTCHA','UNKNOWN_ANSWER','CV_UPLOAD','AUTH','SESSION_EXPIRED'],true)?'BLOCKED_REQUIRES_USER':'FAILED_RETRYING',
                    'blocker'=>$failure,
                    'failure_class'=>$failure,
                    'submission_state_detail'=>$failure,
                    'next_retry_at'=>now()->addMinutes(5),
                ]);
                if(in_array($failure,['CAPTCHA','AUTH','SESSION_EXPIRED'],true)){
                    $this->assist->create($a,$failure,'Complete the required authentication/challenge, then resume this application.',['url'=>$o->official_url?:$o->url]);
                }
                return $a;
            }

            DB::transaction(function()use($a,$o,$data,$pack){
                $a->update([
                    'status'=>'SUBMITTED_CONFIRMED',
                    'route'=>$data['route']??strtolower($a->ats_type??'tinyfish'),
                    'submitted_at'=>now(),
                    'confirmation_at'=>now(),
                    'confirmation_id'=>$data['confirmation_id']??null,
                    'submission_proof'=>json_encode($data['confirmation']),
                    'submission_state_detail'=>'CONFIRMATION_RENDERED',
                    'discovery_to_submit_sec'=>max(0,now()->diffInSeconds($o->discovered_at)),
                    'failure_class'=>null,
                    'next_retry_at'=>null,
                    'resume_document_id'=>(string)$pack['resume']->id,
                    'cover_letter_document_id'=>(string)$pack['cover_letter']->id,
                ]);
            });

            $this->circuit->success('tinyfish');
            $this->followUp->schedule($a);
            $this->sourceMetrics->bump($o->source,'submitted');
            if($o->employer_id){$employer=\App\Models\GalikaEmployer::find($o->employer_id);if($employer)$this->employerMemory->refresh($a->user_id,$employer);}
            return $a->refresh();
        }catch(Throwable $e){
            $this->circuit->failure('tinyfish',$e->getMessage());
            $a->update([
                'status'=>'FAILED_RETRYING',
                'blocker'=>'SITE_ERROR',
                'failure_class'=>'SITE_ERROR',
                'submission_state_detail'=>'SITE_ERROR',
                'next_retry_at'=>now()->addMinutes(min(60,2**min(6,$a->attempt_count))),
            ]);
            report($e);
            return $a;
        }
    }

    private function candidate(int $userId,GalikaProfile $p):array
    {
        return [
            'profile'=>$p->toArray(),
            'evidence'=>GalikaEvidence::where('user_id',$userId)->where('verified',true)
                ->orderByDesc('confidence')->get(['domain','fact','source_type','source_url','confidence','tags'])->toArray(),
        ];
    }
}

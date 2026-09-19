<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaConversation;
use App\Models\GalikaInterview;
use App\Models\GalikaOffer;
use App\Models\GalikaRecruiterThread;
use Illuminate\Support\Str;

class RecruiterThreadService
{
    public function __construct(
        private GmailAdapter $gmail,
        private GalikaIntelligenceService $ai,
        private OutcomeLearningService $learning
    ){}

    public function scan(int $userId):int
    {
        $ids=$this->gmail->search($userId,'newer_than:30d -in:sent',300);$count=0;
        foreach($ids as $row){
            $m=$this->gmail->read($userId,$row['id']);$body=$this->gmail->body($m);
            $subject=$this->gmail->header($m,'Subject')??'';$from=$this->gmail->header($m,'From')??'';
            $hay=mb_strtolower($subject.' '.$from.' '.$body);

            $apps=GalikaApplication::where('user_id',$userId)->where('status','SUBMITTED_CONFIRMED')->with('opportunity')->get()
                ->filter(fn($a)=>$a->opportunity&&(str_contains($hay,mb_strtolower($a->opportunity->employer))||str_contains($hay,mb_strtolower($a->opportunity->title))));

            foreach($apps as $a){
                $classified=$this->ai->classifyRecruiter($userId,$a->opportunity->toArray(),$subject,$from,$body);
                $thread=GalikaRecruiterThread::updateOrCreate(
                    ['user_id'=>$userId,'provider_thread_id'=>$m['threadId']??$row['id']],
                    [
                        'application_id'=>$a->id,'classification'=>$classified['classification'],'state'=>'OPEN',
                        'participants'=>[$from],'last_message'=>Str::limit($body,10000,''),'last_message_at'=>now(),
                        'requires_human'=>$classified['requires_human']
                    ]
                );

                GalikaConversation::create([
                    'user_id'=>$userId,'application_id'=>$a->id,'channel'=>'EMAIL','direction'=>'INBOUND',
                    'external_thread_id'=>$thread->provider_thread_id,'classification'=>$classified['classification'],
                    'body'=>Str::limit($body,15000,''),'occurred_at'=>now(),'metadata'=>['subject'=>$subject,'from'=>$from]
                ]);

                $a->update(['recruiter_thread_state'=>$classified['classification'],'last_inbound_at'=>now(),'inbound_state'=>$classified['classification']]);

                if($classified['classification']==='INTERVIEW'){
                    GalikaInterview::firstOrCreate(['application_id'=>$a->id,'stage'=>'INTERVIEW'],['state'=>'INVITED','timezone'=>null,'prep_plan'=>['status'=>'PENDING']]);
                    $this->learning->record($a,'INTERVIEW','INTERVIEW','RECRUITER_INVITE',['thread'=>$thread->provider_thread_id]);
                }elseif($classified['classification']==='OFFER'){
                    GalikaOffer::firstOrCreate(['application_id'=>$a->id],['state'=>'RECEIVED','analysis'=>['status'=>'PENDING']]);
                    $this->learning->record($a,'OFFER','OFFER','RECRUITER_OFFER',['thread'=>$thread->provider_thread_id]);
                }elseif($classified['classification']==='REJECTION'){
                    $this->learning->record($a,'REJECTION','APPLICATION','RECRUITER_REJECTION',['thread'=>$thread->provider_thread_id]);
                }

                $count++;
            }
        }
        return $count;
    }

    public function maybeRespond(GalikaRecruiterThread $thread,string $fromAddress):array
    {
        if($thread->requires_human)return ['sent'=>false,'reason'=>'HUMAN_REQUIRED'];
        $a=$thread->application()->with('opportunity')->first();
        $draft=$this->ai->safeRecruiterReply($thread->user_id,$a->opportunity->toArray(),$thread->classification,$thread->last_message);
        if($draft['needs_user'])return ['sent'=>false,'reason'=>'HUMAN_REQUIRED'];
        $sent=$this->gmail->send($thread->user_id,$fromAddress,($thread->participants??[])[0]??'', 'Re: '.$a->opportunity->title,$draft['reply'],$thread->provider_thread_id);
        $thread->update(['state'=>'RESPONDED']);
        GalikaConversation::create([
            'user_id'=>$thread->user_id,'application_id'=>$a->id,'channel'=>'EMAIL','direction'=>'OUTBOUND',
            'external_thread_id'=>$thread->provider_thread_id,'classification'=>'SAFE_AUTOREPLY',
            'body'=>$draft['reply'],'occurred_at'=>now(),'metadata'=>['provider_message_id'=>$sent['id']??null]
        ]);
        return ['sent'=>true,'message_id'=>$sent['id']??null];
    }
}

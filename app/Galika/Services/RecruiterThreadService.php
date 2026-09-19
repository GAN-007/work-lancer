<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaRecruiterThread;
use Illuminate\Support\Str;

class RecruiterThreadService
{
    public function __construct(private GmailAdapter $gmail,private GalikaAIService $ai){}

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
                $classified=$this->ai->classifyRecruiterMessage($a->opportunity->toArray(),$subject,$from,$body);
                GalikaRecruiterThread::updateOrCreate(
                    ['user_id'=>$userId,'provider_thread_id'=>$m['threadId']??$row['id']],
                    [
                        'application_id'=>$a->id,'classification'=>$classified['classification'],'state'=>'OPEN',
                        'participants'=>[$from],'last_message'=>Str::limit($body,10000,''),'last_message_at'=>now(),
                        'requires_human'=>$classified['requires_human']
                    ]
                );
                $a->update(['recruiter_thread_state'=>$classified['classification'],'last_inbound_at'=>now(),'inbound_state'=>$classified['classification']]);
                $count++;
            }
        }
        return $count;
    }

    public function maybeRespond(GalikaRecruiterThread $thread,string $fromAddress):array
    {
        if($thread->requires_human) return ['sent'=>false,'reason'=>'HUMAN_REQUIRED'];
        $a=$thread->application()->with('opportunity')->first();
        $draft=$this->ai->safeRecruiterReply($a->opportunity->toArray(),$thread->classification,$thread->last_message);
        if($draft['needs_user']) return ['sent'=>false,'reason'=>'HUMAN_REQUIRED'];
        $sent=$this->gmail->send($thread->user_id,$fromAddress,($thread->participants??[])[0]??'', 'Re: '.$a->opportunity->title,$draft['reply'],$thread->provider_thread_id);
        $thread->update(['state'=>'RESPONDED']);
        return ['sent'=>true,'message_id'=>$sent['id']??null];
    }
}

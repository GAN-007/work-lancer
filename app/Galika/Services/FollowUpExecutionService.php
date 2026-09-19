<?php
namespace App\Galika\Services;
use App\Models\GalikaConversation;use App\Models\GalikaProfile;
class FollowUpExecutionService{
 public function __construct(private FollowUpService $followups,private GmailAdapter $gmail){}
 public function run():int{
  $n=0;
  foreach($this->followups->due() as $a){
   if(!$a->opportunity)continue;
   $profile=GalikaProfile::firstWhere('user_id',$a->user_id);$from=$profile?->email??config('services.gmail.from');
   $target=$this->target($a);if(!$from||!$target){$a->update(['follow_up_state'=>'PAUSED']);continue;}
   $body="Hello,\n\nI'm following up on my application for the {$a->opportunity->title} role at {$a->opportunity->employer}. I'm still very interested and would be glad to provide any additional information.\n\nBest regards";
   $sent=$this->gmail->send($a->user_id,$from,$target,"Re: {$a->opportunity->title}",$body);
   GalikaConversation::create(['user_id'=>$a->user_id,'application_id'=>$a->id,'channel'=>'EMAIL','direction'=>'OUTBOUND','classification'=>'FOLLOW_UP','body'=>$body,'occurred_at'=>now(),'metadata'=>['message_id'=>$sent['id']??null]]);
   $touches=(int)$a->follow_up_touches+1;$a->update(['follow_up_touches'=>$touches]);$this->followups->schedule($a,$touches);$n++;
  }return $n;
 }
 private function target($a):?string{
  if(filter_var($a->route,FILTER_VALIDATE_EMAIL))return $a->route;
  $thread=\App\Models\GalikaRecruiterThread::where('application_id',$a->id)->latest()->first();
  return $thread?($thread->participants[0]??null):null;
 }
}

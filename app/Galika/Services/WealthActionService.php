<?php
namespace App\Galika\Services;
use App\Models\GalikaWealthItem;use App\Models\GalikaConversation;use App\Models\GalikaProfile;
class WealthActionService{
 public function __construct(private GmailAdapter $gmail){}
 public function execute(GalikaWealthItem $i):GalikaWealthItem{
  $plan=$i->execution_plan??[];if(($i->state??'')!=='QUALIFIED'||empty($plan['next_action']))return $i;
  if(!empty($plan['requires_human']))return $i;
  if(($i->lane??'')==='CONSULTING'&&filter_var($i->counterparty,FILTER_VALIDATE_EMAIL)){
   $p=GalikaProfile::firstWhere('user_id',$i->user_id);$from=$p?->email??config('services.gmail.from');
   if($from){
    $body="Hello,\n\nI'm reaching out regarding {$i->title}. Based on my background, I believe I can help and would be glad to discuss scope, timing, and outcomes.\n\nBest regards";
    $sent=$this->gmail->send($i->user_id,$from,$i->counterparty,"Regarding {$i->title}",$body);
    GalikaConversation::create(['user_id'=>$i->user_id,'channel'=>'EMAIL','direction'=>'OUTBOUND','classification'=>'WEALTH_OUTREACH','body'=>$body,'occurred_at'=>now(),'metadata'=>['wealth_item_id'=>$i->id,'message_id'=>$sent['id']??null]]);
    $i->update(['state'=>'EXECUTING','next_action_at'=>now()->addDays(5)]);
   }
  }return $i->refresh();
 }
}

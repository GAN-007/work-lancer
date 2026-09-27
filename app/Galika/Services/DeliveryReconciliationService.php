<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaDeliveryEvent;
use App\Models\GalikaEmailRoute;
use Illuminate\Support\Str;

class DeliveryReconciliationService
{
    public function __construct(private GmailAdapter $gmail){}

    public function reconcile(GalikaApplication $a): array
    {
        if(!$a->user_id||!$a->route||!str_contains($a->route,'@')) return ['state'=>$a->delivery_state,'reason'=>'NOT_EMAIL'];

        $email=mb_strtolower(trim($a->route));
        $route=GalikaEmailRoute::firstOrCreate(
            ['user_id'=>$a->user_id,'email'=>$email],
            ['employer'=>$a->opportunity?->employer??'Unknown','domain'=>Str::after($email,'@')]
        );

        if($route->do_not_retry || $route->state==='HARD_BOUNCED'){
            $a->update(['delivery_state'=>'HARD_BOUNCED','delivery_evidence'=>'Route registry blocks known-dead address','delivery_reconciled_at'=>now()]);
            return ['state'=>'HARD_BOUNCED','reason'=>'ROUTE_REGISTRY_BLOCK'];
        }

        $sent=$this->gmail->search($a->user_id,"in:sent to:{$email} newer_than:30d",100);
        $dsns=$this->gmail->search($a->user_id,'newer_than:30d (from:(mailer-daemon@googlemail.com) OR subject:("Delivery Status Notification") OR subject:(Undeliverable))',100);
        $hard=null;$soft=null;

        foreach($dsns as $row){
            $m=$this->gmail->read($a->user_id,$row['id']);
            $body=$this->gmail->body($m);
            if(!str_contains(mb_strtolower($body),$email)) continue;
            if(preg_match('/\b5\d\d\b|permanent failure|address not found|user unknown|does not exist|domain.*not found/i',$body,$mm)){
                $hard=['id'=>$row['id'],'body'=>$body,'code'=>$mm[0]??'5xx'];break;
            }
            if(preg_match('/\b4\d\d\b|temporar(?:y|ily)|delayed|will retry/i',$body,$mm)){
                $soft=['id'=>$row['id'],'body'=>$body,'code'=>$mm[0]??'4xx'];
            }
        }

        if($hard){
            $route->update(['state'=>'HARD_BOUNCED','last_reconciled_at'=>now(),'hard_bounces'=>$route->hard_bounces+1,'do_not_retry'=>true]);
            $this->event($a,'HARD_BOUNCE',$email,$hard['id'],$hard['code'],$hard['body'],'INVALIDATES_ROUTE',true);
            $a->update(['delivery_state'=>'HARD_BOUNCED','delivery_evidence'=>'Permanent DSN '.$hard['id'],'delivery_reconciled_at'=>now(),'status'=>'FAILED_RETRYING','failure_class'=>'DELIVERY_HARD_BOUNCE']);
            return ['state'=>'HARD_BOUNCED'];
        }

        if($soft){
            $route->update(['state'=>'SENT_PENDING','last_reconciled_at'=>now()]);
            $this->event($a,'SOFT_BOUNCE',$email,$soft['id'],$soft['code'],$soft['body'],'NON_TERMINAL',false);
            $a->update(['delivery_state'=>'SENT_PENDING','delivery_evidence'=>'Temporary DSN '.$soft['id'],'delivery_reconciled_at'=>now()]);
            return ['state'=>'SENT_PENDING'];
        }

        if($sent){
            $route->update(['state'=>'SENT_NO_BOUNCE_OBSERVED','last_reconciled_at'=>now()]);
            $a->update([
                'delivery_state'=>'SENT_NO_BOUNCE_OBSERVED',
                'delivery_evidence'=>'Matching Gmail SENT evidence exists and no matching DSN was observed at reconciliation time; this is not recipient-delivery confirmation.',
                'delivery_reconciled_at'=>now()
            ]);
            return ['state'=>'SENT_NO_BOUNCE_OBSERVED'];
        }

        return ['state'=>'PENDING'];
    }

    private function event(GalikaApplication $a,string $type,string $route,string $id,string $code,string $evidence,string $effect,bool $avoidable): void
    {
        GalikaDeliveryEvent::firstOrCreate(
            ['event_key'=>hash('sha256',$a->id.'|'.$type.'|'.$id)],
            [
                'user_id'=>$a->user_id,'application_id'=>$a->id,'type'=>$type,'event_at'=>now(),
                'route'=>$route,'provider_message_id'=>$id,'dsn_code'=>$code,'evidence'=>Str::limit($evidence,15000,''),
                'terminal_effect'=>$effect,'avoidable_defect'=>$avoidable
            ]
        );
    }
}

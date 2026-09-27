<?php
namespace App\Galika\Services;

use App\Models\GalikaConversation;
use App\Models\GalikaEmailRoute;
use App\Models\GalikaProfile;
use App\Models\GalikaRecruiterThread;

class FollowUpExecutionService
{
    public function __construct(private FollowUpService $followups,private GmailAdapter $gmail){}

    public function run(): int
    {
        $sentCount=0;

        foreach($this->followups->due() as $application){
            if(!$application->opportunity) continue;

            $profile=GalikaProfile::firstWhere('user_id',$application->user_id);
            $from=$profile?->email??config('services.gmail.from');
            $target=$this->target($application);

            if(!$from||!$target){
                $application->update(['follow_up_state'=>'PAUSED']);
                continue;
            }

            $route=GalikaEmailRoute::where('user_id',$application->user_id)
                ->where('email',mb_strtolower($target))
                ->first();

            if($route && ($route->do_not_retry || $route->state==='HARD_BOUNCED')){
                $application->update([
                    'follow_up_state'=>'PAUSED',
                    'delivery_state'=>'HARD_BOUNCED',
                    'delivery_evidence'=>'Follow-up suppressed by authoritative dead-route registry.',
                    'delivery_reconciled_at'=>now(),
                ]);
                continue;
            }

            $alreadySent=GalikaConversation::where('user_id',$application->user_id)
                ->where('application_id',$application->id)
                ->where('classification','FOLLOW_UP')
                ->where('direction','OUTBOUND')
                ->where('created_at','>=',now()->subMinutes(10))
                ->exists();

            if($alreadySent) continue;

            $body="Hello,\n\nI'm following up on my application for the {$application->opportunity->title} role at {$application->opportunity->employer}. I'm still very interested and would be glad to provide any additional information.\n\nBest regards";
            $sent=$this->gmail->send(
                $application->user_id,$from,$target,
                "Re: {$application->opportunity->title}",$body
            );

            GalikaConversation::create([
                'user_id'=>$application->user_id,
                'application_id'=>$application->id,
                'channel'=>'EMAIL',
                'direction'=>'OUTBOUND',
                'classification'=>'FOLLOW_UP',
                'body'=>$body,
                'occurred_at'=>now(),
                'metadata'=>['message_id'=>$sent['id']??null,'recipient'=>$target],
            ]);

            $touches=(int)$application->follow_up_touches+1;
            $application->update(['follow_up_touches'=>$touches]);
            $this->followups->schedule($application,$touches);
            $sentCount++;
        }

        return $sentCount;
    }

    private function target($application): ?string
    {
        if(filter_var($application->route,FILTER_VALIDATE_EMAIL)) return mb_strtolower($application->route);

        $thread=GalikaRecruiterThread::where('application_id',$application->id)->latest()->first();
        $candidate=$thread?($thread->participants[0]??null):null;

        return filter_var($candidate,FILTER_VALIDATE_EMAIL)?mb_strtolower($candidate):null;
    }
}

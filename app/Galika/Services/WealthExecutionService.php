<?php
namespace App\Galika\Services;

use App\Models\GalikaProfile;
use App\Models\GalikaWealthItem;

class WealthExecutionService
{
    public function __construct(private GalikaIntelligenceService $ai){}

    public function plan(GalikaWealthItem $item):GalikaWealthItem
    {
        $profile=GalikaProfile::firstWhere('user_id',$item->user_id);
        $plan=$this->ai->wealthPlan($item->user_id,$profile?->toArray()??[],$item->toArray());
        $item->update([
            'state'=>$plan['viable']?'QUALIFIED':'REJECTED',
            'execution_plan'=>$plan,
            'next_action_at'=>$plan['viable']&&!$plan['requires_human']?now():null,
            'last_error'=>null,
        ]);
        return $item->refresh();
    }
}

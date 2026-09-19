<?php
namespace App\Galika\Services;

use App\Models\GalikaProfile;
use App\Models\GalikaWealthItem;

class WealthExecutionService
{
    public function __construct(private GalikaIntelligenceService $ai,private WealthActionService $actions){}

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
        $item=$item->refresh();
        if($item->state==='QUALIFIED'&&!data_get($item->execution_plan,'requires_human',true))$item=$this->actions->execute($item);
        return $item;
    }
}

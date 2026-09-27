<?php
namespace App\Galika\Services;

use App\Models\GalikaInterview;
use App\Models\GalikaProfile;

class CapacityPlannerService
{
    public function canPursue(int $userId): array
    {
        $profile=GalikaProfile::firstWhere('user_id',$userId);
        if(!$profile) return ['ok'=>false,'reason'=>'NO_PROFILE'];

        $windowStart=now();
        $windowEnd=now()->addDays(7);

        $count=GalikaInterview::whereHas('application',fn($q)=>$q->where('user_id',$userId))
            ->whereNotIn('state',['CANCELLED','DECLINED','COMPLETED'])
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at',[$windowStart,$windowEnd])
            ->count();

        $limit=max(0,(int)$profile->max_interviews_per_week);
        $ok=$count<$limit;

        return [
            'ok'=>$ok,
            'reason'=>$ok?'CAPACITY_AVAILABLE':'INTERVIEW_CAPACITY_REACHED',
            'scheduled'=>$count,
            'limit'=>$limit,
            'window_start'=>$windowStart->toIso8601String(),
            'window_end'=>$windowEnd->toIso8601String(),
        ];
    }
}

<?php
namespace App\Galika\Services;
use App\Models\GalikaInterview;use App\Models\GalikaProfile;
class CapacityPlannerService{
 public function canPursue(int $userId):array{
  $p=GalikaProfile::firstWhere('user_id',$userId);if(!$p)return ['ok'=>false,'reason'=>'NO_PROFILE'];
  $count=GalikaInterview::whereHas('application',fn($q)=>$q->where('user_id',$userId))->whereBetween('starts_at',[now()->startOfWeek(),now()->endOfWeek()])->count();
  return ['ok'=>$count<(int)$p->max_interviews_per_week,'reason'=>$count<(int)$p->max_interviews_per_week?'CAPACITY_AVAILABLE':'INTERVIEW_CAPACITY_REACHED','scheduled'=>$count,'limit'=>(int)$p->max_interviews_per_week];
 }
}

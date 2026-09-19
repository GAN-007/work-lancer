<?php
namespace App\Galika\Services;

use App\Models\GalikaCanaryRun;
use App\Models\GalikaProfile;
use Illuminate\Support\Str;
use Throwable;

class CanaryService
{
    public function run(int $userId):GalikaCanaryRun
    {
        $run=GalikaCanaryRun::create(['user_id'=>$userId,'run_key'=>(string)Str::uuid(),'scenario'=>'CAREER_END_TO_END','state'=>'RUNNING','started_at'=>now(),'steps'=>[]]);
        $steps=[];
        try{
            $profile=GalikaProfile::firstWhere('user_id',$userId);$steps[]=['step'=>'profile','ok'=>(bool)$profile];
            $steps[]=['step'=>'autonomy','ok'=>(bool)($profile?->autonomous_apply_enabled)];
            $run->update(['state'=>'PASSED','steps'=>$steps,'finished_at'=>now()]);
        }catch(Throwable $e){
            $run->update(['state'=>'FAILED','steps'=>$steps,'failure'=>$e->getMessage(),'finished_at'=>now()]);
        }
        return $run->refresh();
    }
}

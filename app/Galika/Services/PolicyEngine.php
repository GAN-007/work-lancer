<?php
namespace App\Galika\Services;

use App\Models\GalikaPolicy;
use App\Models\GalikaProfile;

class PolicyEngine{
    public function evaluate(int $userId,array $job):array{
        $profile=GalikaProfile::firstWhere('user_id',$userId);
        if(!$profile)return ['allowed'=>false,'reason'=>'PROFILE_MISSING'];
        if($profile->pause_all_execution)return ['allowed'=>false,'reason'=>'EXECUTION_PAUSED'];
        $policies=GalikaPolicy::where('user_id',$userId)->where('enabled',true)->orderBy('priority')->get();
        foreach($policies as $p){$rule=$p->rule??[];$kind=$rule['kind']??null;$value=$rule['value']??null;if(!$kind)continue;
            $hay=mb_strtolower(json_encode($job));
            $matched=$value!==null&&str_contains($hay,mb_strtolower((string)$value));
            if($matched&&$p->operator==='DENY')return ['allowed'=>false,'reason'=>'POLICY_'.$p->policy_key];
        }
        return ['allowed'=>true,'reason'=>'PASS'];
    }
}

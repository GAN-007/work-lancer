<?php
namespace App\Galika\Services;

use App\Models\GalikaPlatformHealth;

class PlatformCircuitBreaker{
    public function available(string $platform):bool{
        $h=GalikaPlatformHealth::firstWhere('platform',$platform);
        if(!$h)return true;
        if($h->circuit_state==='OPEN'&&$h->retry_after&&$h->retry_after->isFuture())return false;
        return true;
    }
    public function success(string $platform):void{
        GalikaPlatformHealth::updateOrCreate(['platform'=>$platform],['health'=>'HEALTHY','circuit_state'=>'CLOSED','consecutive_failures'=>0,'last_checked'=>now(),'last_success'=>now(),'retry_after'=>null,'last_error'=>null]);
    }
    public function failure(string $platform,string $error,int $threshold=3):void{
        $h=GalikaPlatformHealth::firstOrCreate(['platform'=>$platform]);
        $h->consecutive_failures=(int)$h->consecutive_failures+1;$h->last_checked=now();$h->health='DEGRADED';$h->last_error=$error;
        if($h->consecutive_failures>=$threshold){$h->circuit_state='OPEN';$h->retry_after=now()->addMinutes(min(60,2**min(6,$h->consecutive_failures)));}
        $h->save();
    }
}

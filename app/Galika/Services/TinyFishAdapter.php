<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TinyFishAdapter{
    public function apply(GalikaApplication $a,GalikaOpportunity $o,array $candidate,array $answers=[]):array{
        $endpoint=config('services.tinyfish.endpoint');$key=config('services.tinyfish.key');if(!$endpoint||!$key)throw new RuntimeException('TinyFish runtime credentials missing');
        $r=Http::withToken($key)->timeout(180)->post($endpoint,['url'=>$o->official_url?:$o->url,'goal'=>'Apply to this job for the supplied candidate and stop before any unsupported material answer, CAPTCHA, or destructive/ambiguous step. Return explicit submission confirmation evidence only after final submission.','candidate'=>$candidate,'answers'=>$answers,'rules'=>['require_confirmation'=>true,'do_not_invent'=>true,'stop_on_captcha'=>true]]);
        if(!$r->successful())throw new RuntimeException('TinyFish failed '.$r->status().': '.$r->body());return $r->json();
    }
}

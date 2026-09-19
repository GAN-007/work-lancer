<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TinyFishAdapter
{
    public function __construct(private ConnectionVault $vault){}

    public function apply(GalikaApplication $a,GalikaOpportunity $o,array $candidate,array $answers=[],array $attachments=[]):array
    {
        $endpoint=config('services.tinyfish.endpoint');
        $key=$this->vault->credential($a->user_id,'tinyfish','api_key');
        if(!$endpoint) throw new RuntimeException('TinyFish endpoint missing');

        $r=Http::withToken($key)->timeout(180)->post($endpoint,[
            'url'=>$o->official_url?:$o->url,
            'goal'=>'Apply to this job for the supplied candidate. Use supplied verified answers and attachments. Never invent material facts. Stop on CAPTCHA, authentication failure, unsupported material questions, or ambiguous/destructive steps. Return explicit final submission confirmation evidence only after successful submission.',
            'candidate'=>$candidate,
            'answers'=>$answers,
            'attachments'=>$attachments,
            'rules'=>[
                'require_confirmation'=>true,
                'do_not_invent'=>true,
                'stop_on_captcha'=>true,
                'stop_on_auth_failure'=>true,
                'preserve_evidence'=>true,
            ],
        ]);
        if(!$r->successful()) throw new RuntimeException('TinyFish failed '.$r->status().': '.$r->body());
        return $r->json();
    }
}

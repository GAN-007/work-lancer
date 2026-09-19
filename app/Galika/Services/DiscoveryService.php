<?php
namespace App\Galika\Services;

use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscoveryService{
    public function __construct(private CanonicalizationService $canonicalizer,private PlatformCircuitBreaker $circuit){}
    public function discover():int{
        $created=0;if(!$this->circuit->available('jobicy'))return 0;
        foreach(config('galika.discovery.queries') as $q){
            $r=Http::timeout(30)->retry(2,250)->get(config('services.jobicy.endpoint','https://jobicy.com/api/v2/remote-jobs'),['count'=>50,'tag'=>Str::slug($q)]);
            if(!$r->successful()){$this->circuit->failure('jobicy',$r->body());continue;}
            foreach($r->json('jobs',[]) as $j){$external=(string)($j['id']??sha1(($j['companyName']??'').'|'.($j['jobTitle']??'').'|'.($j['url']??'')));$payload=['source'=>'jobicy','external_id'=>$external,'employer'=>$j['companyName']??'Unknown','title'=>$j['jobTitle']??'Unknown','location'=>$j['jobGeo']??null,'url'=>$j['url']??'','description'=>$j['jobDescription']??null,'published_at'=>isset($j['pubDate'])?date('Y-m-d H:i:s',strtotime($j['pubDate'])):null];$fp=$this->canonicalizer->fingerprint($payload);$existing=$this->canonicalizer->resolve($payload);if($existing){continue;}$key=hash('sha256','jobicy|'.$external);$o=GalikaOpportunity::firstOrCreate(['canonical_key'=>$key],$payload+['fingerprint'=>$fp,'source_authority'=>'AGGREGATOR','discovered_at'=>now(),'evidence'=>$j]);if($o->wasRecentlyCreated)$created++;}
        }$this->circuit->success('jobicy');return $created;
    }
}

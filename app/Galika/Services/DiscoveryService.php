<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class DiscoveryService {
 public function discover(): int {
  $created=0; foreach(config('galika.discovery.queries',['AI Engineer','Data Scientist','Full Stack Developer','Machine Learning Engineer','Technical Lead']) as $q){
   $url='https://jobicy.com/api/v2/remote-jobs'; $r=Http::timeout(30)->get($url,['count'=>50,'tag'=>Str::slug($q)]); if(!$r->successful()) continue;
   foreach($r->json('jobs',[]) as $j){$external=(string)($j['id']??sha1(($j['companyName']??'').'|'.($j['jobTitle']??'').'|'.($j['url']??'')));$key=hash('sha256','jobicy|'.$external);
    $o=GalikaOpportunity::firstOrCreate(['canonical_key'=>$key],['source'=>'jobicy','external_id'=>$external,'employer'=>$j['companyName']??'Unknown','title'=>$j['jobTitle']??'Unknown','location'=>$j['jobGeo']??null,'url'=>$j['url']??'','description'=>$j['jobDescription']??null,'published_at'=>isset($j['pubDate'])?date('Y-m-d H:i:s',strtotime($j['pubDate'])):null,'discovered_at'=>now(),'evidence'=>$j]); if($o->wasRecentlyCreated)$created++;
   }
  } return $created;
 }
}

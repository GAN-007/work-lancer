<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
class SourceBrokerService{
 public function __construct(private CanonicalizationService $canonical,private PlatformCircuitBreaker $circuit,private GmailAdapter $gmail,private SourceMetricService $metrics){}
 public function discover():int{
  $count=$this->jobicy();
  GalikaProfile::where('pause_all_execution',false)->each(function($p)use(&$count){try{$count+=$this->gmailAlerts($p->user_id);}catch(\Throwable $e){report($e);}});
  return $count;
 }
 private function jobicy():int{
  if(!$this->circuit->available('jobicy'))return 0;$created=0;
  foreach(config('galika.discovery.queries') as $q){
   $r=Http::timeout(30)->retry(2,250)->get(config('services.jobicy.endpoint','https://jobicy.com/api/v2/remote-jobs'),['count'=>50,'tag'=>Str::slug($q)]);
   if(!$r->successful()){$this->circuit->failure('jobicy',$r->body());continue;}
   foreach($r->json('jobs',[]) as $j){
    $payload=['source'=>'jobicy','external_id'=>(string)($j['id']??sha1(json_encode($j))),'employer'=>$j['companyName']??'Unknown','title'=>$j['jobTitle']??'Unknown','location'=>$j['jobGeo']??null,'url'=>$j['url']??'','description'=>$j['jobDescription']??null,'published_at'=>isset($j['pubDate'])?date('Y-m-d H:i:s',strtotime($j['pubDate'])):null];
    if($this->persist($payload,$j)){$created++;$this->metrics->bump('jobicy','discovered');}
   }
  }$this->circuit->success('jobicy');return $created;
 }
 private function gmailAlerts(int $userId):int{
  $created=0;
  foreach($this->gmail->search($userId,'newer_than:7d (subject:(job OR vacancy OR opportunity OR hiring) OR from:(linkedin OR lever OR greenhouse OR workday))',100) as $row){
   $m=$this->gmail->read($userId,$row['id']);$body=$this->gmail->body($m);$subject=$this->gmail->header($m,'Subject')??'';
   preg_match_all('~https?://[^\s<>"\']+~i',$body,$links);
   foreach(array_unique($links[0]??[]) as $url){
    if(!preg_match('~(linkedin\.com/jobs|lever\.co|greenhouse\.io|myworkdayjobs\.com|ashbyhq\.com|smartrecruiters\.com|/careers?/|/jobs?/)~i',$url))continue;
    $payload=['source'=>'gmail_alert','external_id'=>sha1($url),'employer'=>$this->employerFromSubject($subject),'title'=>$subject?:'Job opportunity','location'=>null,'url'=>rtrim($url,').,]'),'description'=>Str::limit(strip_tags($body),5000,''),'published_at'=>now()->toDateTimeString()];
    if($this->persist($payload,['message_id'=>$row['id'],'subject'=>$subject])){$created++;$this->metrics->bump('gmail_alert','discovered');}
   }
  }
  return $created;
 }
 private function employerFromSubject(string $s):string{
  $s=preg_replace('/^(new|job alert|opportunity|hiring)[:\-\s]+/i','',$s);return trim(Str::before($s,' - '))?:'Unknown';
 }
 private function persist(array $payload,array $evidence):bool{
  if(empty($payload['url']))return false;
  if($this->canonical->resolve($payload))return false;
  $fp=$this->canonical->fingerprint($payload);
  $o=GalikaOpportunity::firstOrCreate(['canonical_key'=>hash('sha256',$payload['source'].'|'.$payload['external_id'])],$payload+['fingerprint'=>$fp,'source_authority'=>$payload['source']==='gmail_alert'?'SECONDARY':'AGGREGATOR','discovered_at'=>now(),'evidence'=>$evidence]);
  return $o->wasRecentlyCreated;
 }
}

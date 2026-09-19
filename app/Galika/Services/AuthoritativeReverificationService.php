<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
class AuthoritativeReverificationService{
 public function __construct(private RoleLineageService $lineage){}
 public function verify(GalikaOpportunity $o):array{
  $url=$o->official_url?:$o->url;if(!$url)return ['open'=>false,'reason'=>'NO_URL'];
  try{
   if($o->source==='greenhouse'&&$o->requisition_id){
    $board=parse_url($url,PHP_URL_HOST)?explode('.',parse_url($url,PHP_URL_HOST))[0]:null;
    if($board){
      $r=Http::timeout(30)->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs/{$o->requisition_id}");
      if($r->status()===404){$o->update(['official_open'=>false,'official_checked_at'=>now(),'reverified_at'=>now()]);return ['open'=>false,'reason'=>'GREENHOUSE_CLOSED'];}
      if($r->successful()){$j=$r->json();$this->lineage->observe($o,$j);$o->update(['official_open'=>true,'official_checked_at'=>now(),'reverified_at'=>now()]);return ['open'=>true,'reason'=>'GREENHOUSE_OPEN'];}
    }
   }
   $r=Http::timeout(30)->withHeaders(['User-Agent'=>'Work-Lancer-GALIKA/2.0'])->get($url);
   if(in_array($r->status(),[404,410],true)){ $o->update(['official_open'=>false,'official_checked_at'=>now(),'reverified_at'=>now()]); return ['open'=>false,'reason'=>'CLOSED_HTTP'];}
   if(!$r->successful())return ['open'=>true,'reason'=>'UNVERIFIED_HTTP_'.$r->status(),'soft'=>true];
   $body=mb_strtolower(strip_tags($r->body()));
   foreach(['job is no longer available','position has been filled','job has expired','applications are closed','no longer accepting applications'] as $closed){
    if(str_contains($body,$closed)){ $o->update(['official_open'=>false,'official_checked_at'=>now(),'reverified_at'=>now()]); return ['open'=>false,'reason'=>'CLOSED_TEXT'];}
   }
   $this->lineage->observe($o,['title'=>$o->title,'location'=>$o->location,'description'=>$o->description,'requisition_id'=>$o->requisition_id]);
   $o->update(['official_open'=>true,'official_checked_at'=>now(),'reverified_at'=>now(),'official_url'=>$url]);
   return ['open'=>true,'reason'=>'AUTHORITATIVE_OPEN'];
  }catch(\Throwable $e){return ['open'=>true,'reason'=>'REVERIFY_ERROR','soft'=>true,'error'=>$e->getMessage()];}
 }
}

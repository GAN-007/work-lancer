<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
class AuthoritativeReverificationService{
 public function verify(GalikaOpportunity $o):array{
  $url=$o->official_url?:$o->url;
  if(!$url)return ['open'=>false,'reason'=>'NO_URL'];
  try{
   $r=Http::timeout(30)->withHeaders(['User-Agent'=>'Work-Lancer-GALIKA/2.0'])->get($url);
   if(in_array($r->status(),[404,410],true)){ $o->update(['official_open'=>false,'official_checked_at'=>now(),'reverified_at'=>now()]); return ['open'=>false,'reason'=>'CLOSED_HTTP'];}
   if(!$r->successful())return ['open'=>true,'reason'=>'UNVERIFIED_HTTP_'.$r->status(),'soft'=>true];
   $body=mb_strtolower(strip_tags($r->body()));
   foreach(['job is no longer available','position has been filled','job has expired','applications are closed','no longer accepting applications'] as $closed){
    if(str_contains($body,$closed)){ $o->update(['official_open'=>false,'official_checked_at'=>now(),'reverified_at'=>now()]); return ['open'=>false,'reason'=>'CLOSED_TEXT'];}
   }
   $o->update(['official_open'=>true,'official_checked_at'=>now(),'reverified_at'=>now(),'official_url'=>$url]);
   return ['open'=>true,'reason'=>'AUTHORITATIVE_OPEN'];
  }catch(\Throwable $e){return ['open'=>true,'reason'=>'REVERIFY_ERROR','soft'=>true,'error'=>$e->getMessage()];}
 }
}

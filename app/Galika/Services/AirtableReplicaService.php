<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaOpportunity;use Illuminate\Support\Facades\DB;use RuntimeException;
class AirtableReplicaService{
 public function __construct(private AirtableAdapter $airtable){}
 public function replicate(array $event):void{
  $topic=$event['topic'];$payload=json_decode($event['payload']??'{}',true)?:[];$uid=(int)($event['user_id']??0);if(!$uid)throw new RuntimeException('Replica event has no user');
  if(str_starts_with($topic,'application.')){$a=GalikaApplication::with('opportunity')->findOrFail($event['aggregate_id']);$this->airtable->upsert($uid,'Application Ledger','Application Key',['Application Key'=>$a->application_key,'Status'=>$a->status,'Failure Class'=>$a->failure_class,'Blocker'=>$a->blocker,'Route'=>$a->route,'Delivery State'=>$a->delivery_state,'Confirmation ID'=>$a->confirmation_id,'Updated At'=>$a->updated_at?->toIso8601String()]);}
  elseif(str_starts_with($topic,'opportunity.')){$o=GalikaOpportunity::findOrFail($event['aggregate_id']);$this->airtable->upsert($uid,'Revenue Pipeline','Canonical Key',['Canonical Key'=>$o->canonical_key,'Title'=>$o->title,'URL'=>$o->official_url?:$o->url,'Eligibility'=>$o->eligibility,'Official Open'=>(bool)$o->official_open,'Updated At'=>$o->updated_at?->toIso8601String()]);}
  DB::table('galika_replica_checkpoints')->updateOrInsert(['sink'=>'airtable','user_id'=>$uid],['last_outbox_id'=>$event['id'],'last_success_at'=>now(),'last_error'=>null,'updated_at'=>now(),'created_at'=>now()]);
 }
}
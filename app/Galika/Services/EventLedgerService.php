<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class EventLedgerService {
 public function record(int $userId,string $aggregateType,?int $aggregateId,string $eventType,array $payload=[]):string {
  $id=(string)Str::uuid();
  DB::table('galika_event_ledger')->insert(['event_id'=>$id,'user_id'=>$userId,'aggregate_type'=>$aggregateType,'aggregate_id'=>$aggregateId,'event_type'=>$eventType,'payload'=>json_encode($payload),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
  return $id;
 }
 public function outbox(int $userId,string $topic,array $payload,?\DateTimeInterface $availableAt=null):string {
  $id=(string)Str::uuid();
  DB::table('galika_outbox')->insert(['event_id'=>$id,'user_id'=>$userId,'topic'=>$topic,'payload'=>json_encode($payload),'status'=>'PENDING','attempts'=>0,'available_at'=>$availableAt??now(),'created_at'=>now(),'updated_at'=>now()]);
  return $id;
 }
}
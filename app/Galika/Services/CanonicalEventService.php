<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CanonicalEventService{
 public function record(int $userId,string $aggregateType,?int $aggregateId,string $eventType,array $payload=[],array $replicas=[]):string{
  return DB::transaction(function()use($userId,$aggregateType,$aggregateId,$eventType,$payload,$replicas){
   $correlation=(string)Str::uuid();
   DB::table('galika_canonical_events')->insert(['user_id'=>$userId,'aggregate_type'=>$aggregateType,'aggregate_id'=>$aggregateId,'event_type'=>$eventType,'correlation_id'=>$correlation,'payload'=>json_encode($payload),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
   foreach($replicas as $destination) DB::table('galika_outbox')->insert(['event_id'=>(string)Str::uuid(),'user_id'=>$userId,'destination'=>$destination,'kind'=>$eventType,'payload'=>json_encode(array_merge($payload,['aggregate_type'=>$aggregateType,'aggregate_id'=>$aggregateId,'correlation_id'=>$correlation])),'status'=>'PENDING','available_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
   return $correlation;
  });
 }
}
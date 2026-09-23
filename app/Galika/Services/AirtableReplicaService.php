<?php
namespace App\Galika\Services;
class AirtableReplicaService{
 public function __construct(private AirtableAdapter $airtable){}
 public function project(int $userId,string $kind,array $payload):void{
  $table=config('galika.airtable.replica_table','Canonical Event Ledger');
  $key=$payload['correlation_id']??hash('sha256',$kind.'|'.json_encode($payload));
  $this->airtable->upsert($userId,$table,'Correlation ID',['Correlation ID'=>$key,'Event Type'=>$kind,'Payload'=>json_encode($payload),'Synced At'=>now()->toIso8601String()]);
 }
}
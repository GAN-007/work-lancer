<?php
namespace App\Console\Commands;
use App\Galika\Services\AirtableReplicaService;
use App\Galika\Services\OutboxService;
use Illuminate\Console\Command;
class GalikaOutboxWorker extends Command{
 protected $signature='galika:outbox {--limit=50}';protected $description='Deliver durable external side effects without placing them on the critical path';
 public function handle(OutboxService $outbox,AirtableReplicaService $airtable):int{
  foreach($outbox->lease((int)$this->option('limit')) as $row){
   try{
    $payload=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR)?:[];
    if(($row['destination']??null)==='airtable') $airtable->project((int)$row['user_id'],(string)$row['kind'],$payload);
    else throw new \RuntimeException('Unsupported outbox destination '.($row['destination']??''));
    $outbox->done((int)$row['id']);
   }catch(\Throwable $e){$outbox->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));report($e);}
  }
  return self::SUCCESS;
 }
}

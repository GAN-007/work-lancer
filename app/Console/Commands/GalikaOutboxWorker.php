<?php
namespace App\Console\Commands;
use App\Galika\Services\AirtableReplicaService;
use App\Galika\Services\OutboxService;
use Illuminate\Console\Command;
class GalikaOutboxWorker extends Command {
 protected $signature='galika:outbox-worker {--limit=50} {--sleep=2} {--once}';
 protected $description='Continuously drain the PostgreSQL authoritative GALIKA outbox';
 public function handle(OutboxService $outbox,AirtableReplicaService $airtable):int {
  do {
   $rows=$outbox->lease((int)$this->option('limit'));
   foreach($rows as $row){try{$p=json_decode($row['payload']??'{}',true)?:[];if($row['topic']==='AIRTABLE_APPLICATION_REPLICA')$airtable->syncApplication((int)$row['user_id'],(int)$p['application_id']);$outbox->done((int)$row['id']);}catch(\Throwable $e){$outbox->retry((int)$row['id'],$e->getMessage(),(int)$row['attempts']);report($e);}}
   if($this->option('once'))break;if(!$rows)sleep(max(1,(int)$this->option('sleep')));
  }while(true);
  return self::SUCCESS;
 }
}
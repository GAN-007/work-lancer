<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Galika\Services\{OutboxService,AirtableReplicaService};
class GalikaOutbox extends Command{
 protected $signature='galika:outbox {--limit=50}'; protected $description='Deliver durable GALIKA outbox events; Postgres remains authoritative';
 public function handle(OutboxService $outbox,AirtableReplicaService $airtable):int{
  foreach($outbox->lease((int)$this->option('limit')) as $item){try{$payload=json_decode($item['payload'],true)?:[];if($item['destination']==='airtable')$airtable->project((int)$item['user_id'],$item['kind'],$payload);else throw new \RuntimeException('Unknown outbox destination '.$item['destination']);$outbox->done((int)$item['id']);}catch(\Throwable $e){$outbox->retry((int)$item['id'],$e->getMessage(),(int)$item['attempts']);report($e);}}
  return self::SUCCESS;
 }
}
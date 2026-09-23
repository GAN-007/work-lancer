<?php
namespace App\Console\Commands;
use App\Galika\Services\AirtableReplicaService;
use App\Galika\Services\GmailAdapter;
use App\Galika\Services\OutboxService;
use Illuminate\Console\Command;
class GalikaOutboxWorker extends Command{
 protected $signature='galika:outbox {--limit=50}';protected $description='Deliver durable external side effects without placing them on the critical path';
 public function handle(OutboxService $outbox,AirtableReplicaService $airtable,GmailAdapter $gmail):int{
  foreach($outbox->lease((int)$this->option('limit')) as $row){try{$p=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);if($row['destination']==='AIRTABLE'&&$row['operation']==='APPLICATION_UPSERT')$airtable->syncApplication((int)$row['user_id'],(int)$p['application_id']);elseif($row['destination']==='AIRTABLE'&&$row['operation']==='OPPORTUNITY_UPSERT')$airtable->syncOpportunity((int)$row['user_id'],(int)$p['opportunity_id']);elseif($row['destination']==='GMAIL'&&$row['operation']==='SEND')$gmail->send((int)$row['user_id'],$p['from'],$p['to'],$p['subject'],$p['body'],$p['thread_id']??null,$p['message_id']??null);else throw new \RuntimeException('Unsupported outbox operation '.$row['destination'].'/'.$row['operation']);$outbox->delivered((int)$row['id']);}catch(\Throwable $e){$outbox->retry((int)$row['id'],$e->getMessage(),(int)$row['attempts']);report($e);}}
  return self::SUCCESS;
 }
}
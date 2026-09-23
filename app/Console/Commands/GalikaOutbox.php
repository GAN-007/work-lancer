<?php
namespace App\Console\Commands;
use App\Galika\Services\AirtableReplicaService;use App\Galika\Services\OutboxService;use Illuminate\Console\Command;
class GalikaOutbox extends Command{
 protected $signature='galika:outbox {--limit=50}';protected $description='Deliver durable GALIKA outbox events to non-authoritative replicas';
 public function handle(OutboxService $out,AirtableReplicaService $replica):int{foreach($out->lease((int)$this->option('limit')) as $row){try{$replica->replicate($row);$out->done((int)$row['id']);}catch(\Throwable $e){$out->retry((int)$row['id'],$e->getMessage(),(int)($row['attempts']??1));report($e);}}return self::SUCCESS;}
}
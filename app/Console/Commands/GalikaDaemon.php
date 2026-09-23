<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Support\Facades\Artisan;use Illuminate\Support\Facades\DB;use Illuminate\Support\Str;
class GalikaDaemon extends Command{
 protected $signature='galika:daemon {--sleep=2} {--batch=25}';protected $description='Always-on PostgreSQL-authoritative GALIKA execution daemon';
 public function handle():int{$instance=(string)Str::uuid();while(true){DB::table('galika_worker_heartbeats')->updateOrInsert(['worker'=>'execution'],['instance_id'=>$instance,'heartbeat_at'=>now(),'meta'=>json_encode(['pid'=>getmypid()]),'created_at'=>now(),'updated_at'=>now()]);Artisan::call('galika:worker',['--limit'=>(int)$this->option('batch')]);Artisan::call('galika:inbound');Artisan::call('galika:outbox',['--limit'=>(int)$this->option('batch')]);sleep(max(1,(int)$this->option('sleep')));}return self::SUCCESS;}
}
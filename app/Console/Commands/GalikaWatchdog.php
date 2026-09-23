<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class GalikaWatchdog extends Command {
 protected $signature='galika:watchdog {--max-age=90}';
 protected $description='Fail when the always-on GALIKA application worker heartbeat is stale';
 public function handle():int {
  $h=DB::table('galika_worker_heartbeats')->where('worker','application')->first();
  if(!$h||!$h->heartbeat_at||now()->diffInSeconds($h->heartbeat_at)>(int)$this->option('max-age')){$this->error('GALIKA application worker is dead/stale; restart required');return self::FAILURE;}
  $this->info('GALIKA application worker healthy');return self::SUCCESS;
 }
}
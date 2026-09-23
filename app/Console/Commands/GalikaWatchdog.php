<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class GalikaWatchdog extends Command{
 protected $signature='galika:watchdog {--stale=180}';protected $description='Detect stale GALIKA workers and make abandoned leases runnable again';
 public function handle():int{$cut=now()->subSeconds((int)$this->option('stale'));$released=DB::table('galika_execution_work_items')->where('status','LEASED')->where('leased_until','<',now())->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>now(),'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>now()]);$out=DB::table('galika_outbox')->where('status','LEASED')->where('leased_until','<',now())->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>now(),'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>now()]);$this->info("Recovered {$released} work leases and {$out} outbox leases.");return self::SUCCESS;}
}
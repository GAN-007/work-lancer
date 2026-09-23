<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Support\Facades\DB;
class GalikaWatchdog extends Command{
 protected $signature='galika:watchdog';protected $description='Recover expired leases and expose dead-worker health without pretending work completed';
 public function handle():int{$now=now();$reclaimed=DB::table('galika_execution_work_items')->whereIn('status',['LEASED','RUNNING'])->whereNotNull('leased_until')->where('leased_until','<',$now)->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECLAIMED_EXPIRED_LEASE','updated_at'=>$now]);$out=DB::table('galika_outbox')->where('status','LEASED')->whereNotNull('leased_until')->where('leased_until','<',$now)->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECLAIMED_EXPIRED_LEASE','updated_at'=>$now]);$this->info("Reclaimed work={$reclaimed}; outbox={$out}");return self::SUCCESS;}
}
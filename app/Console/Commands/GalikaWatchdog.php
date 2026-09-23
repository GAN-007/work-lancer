<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class GalikaWatchdog extends Command{
 protected $signature='galika:watchdog'; protected $description='Recover expired GALIKA work leases independently of application workers';
 public function handle():int{
  $now=now();$recovered=DB::table('galika_execution_work_items')->whereIn('status',['LEASED','RUNNING'])->whereNotNull('leased_until')->where('leased_until','<',$now)->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>$now]);
  $outbox=DB::table('galika_outbox')->where('status','LEASED')->whereNotNull('leased_until')->where('leased_until','<',$now)->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>$now]);
  $this->info("recovered_work={$recovered} recovered_outbox={$outbox}");return self::SUCCESS;
 }
}
<?php
namespace App\Console\Commands;

use App\Galika\Services\RuntimeAssuranceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GalikaWatchdog extends Command
{
    protected $signature='galika:watchdog';
    protected $description='Recover stale leases and fail when the authoritative execution runtime is unhealthy';

    public function handle(RuntimeAssuranceService $runtime): int
    {
        $now=now();

        $recovered=DB::table('galika_execution_work_items')
            ->whereIn('status',['LEASED','RUNNING'])
            ->whereNotNull('leased_until')->where('leased_until','<',$now)
            ->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>$now]);

        $outbox=DB::table('galika_outbox')
            ->where('status','LEASED')->whereNotNull('leased_until')->where('leased_until','<',$now)
            ->update(['status'=>'RETRY','leased_until'=>null,'available_at'=>$now,'last_error'=>'WATCHDOG_RECOVERED_EXPIRED_LEASE','updated_at'=>$now]);

        $checks=$runtime->checks();
        $runtime->recordFailures($checks);
        $this->info("recovered_work={$recovered} recovered_outbox={$outbox}");

        foreach($checks as $name=>$check){
            $this->line(($check['ok']?'PASS ':'FAIL ').$name.' '.$check['detail']);
        }

        return $runtime->overall($checks)?self::SUCCESS:self::FAILURE;
    }
}

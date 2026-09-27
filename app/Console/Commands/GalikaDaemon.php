<?php
namespace App\Console\Commands;

use App\Galika\Services\CampaignExecutionService;
use App\Galika\Services\FollowUpExecutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GalikaDaemon extends Command
{
    protected $signature='galika:daemon {--sleep=2} {--batch=25}';
    protected $description='Always-on PostgreSQL-authoritative GALIKA execution daemon';

    public function handle(FollowUpExecutionService $followups, CampaignExecutionService $campaigns): int
    {
        $instance=(string)Str::uuid();
        $sleep=max(1,(int)$this->option('sleep'));
        $batch=max(1,(int)$this->option('batch'));

        while(true){
            DB::table('galika_worker_heartbeats')->updateOrInsert(
                ['worker'=>'execution'],
                [
                    'instance_id'=>$instance,
                    'heartbeat_at'=>now(),
                    'meta'=>json_encode(['pid'=>getmypid(),'batch'=>$batch]),
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]
            );

            try {
                Artisan::call('galika:worker',['--limit'=>$batch]);
                Artisan::call('galika:inbound');
                $followups->run();
                $campaigns->executeDue($batch);
                Artisan::call('galika:wealth',['--limit'=>$batch]);
                Artisan::call('galika:outbox',['--limit'=>$batch]);
                Artisan::call('galika:reconcile-acks');
            } catch (\Throwable $e) {
                report($e);
                DB::table('galika_runtime_incidents')->updateOrInsert(
                    ['fingerprint'=>hash('sha256','daemon|'.$e->getMessage())],
                    [
                        'severity'=>'CRITICAL','component'=>'galika-daemon','code'=>'DAEMON_LOOP_EXCEPTION',
                        'message'=>$e->getMessage(),'context'=>json_encode(['exception'=>get_class($e)]),
                        'state'=>'OPEN','first_seen_at'=>now(),'last_seen_at'=>now(),'created_at'=>now(),'updated_at'=>now()
                    ]
                );
            }

            sleep($sleep);
        }
    }
}

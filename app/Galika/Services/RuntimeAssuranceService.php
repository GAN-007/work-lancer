<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RuntimeAssuranceService
{
    public function checks(): array
    {
        $checks=[];
        $checks['database']=$this->capture(fn()=>DB::select('select 1'));
        foreach ([
            'galika_execution_work_items',
            'galika_outbox',
            'galika_worker_heartbeats',
            'galika_applications',
            'galika_opportunities',
            'galika_application_answers',
            'galika_email_routes',
            'galika_delivery_events',
            'galika_runtime_incidents',
            'galika_scorecards',
        ] as $table) {
            $checks['table:'.$table]=Schema::hasTable($table)
                ? ['ok'=>true,'detail'=>'present']
                : ['ok'=>false,'detail'=>'missing'];
        }

        $heartbeat=Schema::hasTable('galika_worker_heartbeats')
            ? DB::table('galika_worker_heartbeats')->where('worker','execution')->first()
            : null;
        $age=$heartbeat?->heartbeat_at ? now()->diffInSeconds($heartbeat->heartbeat_at) : null;
        $checks['worker:execution']=[
            'ok'=>$age!==null && $age<=90,
            'detail'=>$age===null?'missing heartbeat':'heartbeat_age_sec='.$age,
        ];

        $queued=Schema::hasTable('galika_execution_work_items')
            ? DB::table('galika_execution_work_items')->whereIn('status',['QUEUED','RETRY'])->count()
            : null;
        $oldest=Schema::hasTable('galika_execution_work_items')
            ? DB::table('galika_execution_work_items')->whereIn('status',['QUEUED','RETRY'])->min('created_at')
            : null;
        $oldestAge=$oldest?now()->diffInSeconds($oldest):0;
        $checks['queue:freshness']=[
            'ok'=>$oldestAge<=7200,
            'detail'=>'queued='.($queued??0).' oldest_age_sec='.$oldestAge,
        ];

        return $checks;
    }

    public function overall(array $checks): bool
    {
        foreach ($checks as $check) if (!($check['ok']??false)) return false;
        return true;
    }

    public function recordFailures(array $checks): void
    {
        if (!Schema::hasTable('galika_runtime_incidents')) return;
        foreach ($checks as $component=>$check) {
            if ($check['ok']??false) continue;
            $message=(string)($check['detail']??'failed');
            $fingerprint=hash('sha256',$component.'|'.$message);
            DB::table('galika_runtime_incidents')->updateOrInsert(
                ['fingerprint'=>$fingerprint],
                [
                    'severity'=>'ERROR',
                    'component'=>$component,
                    'code'=>'RUNTIME_CHECK_FAILED',
                    'message'=>$message,
                    'context'=>json_encode(['check'=>$check]),
                    'state'=>'OPEN',
                    'first_seen_at'=>DB::raw('COALESCE(first_seen_at, CURRENT_TIMESTAMP)'),
                    'last_seen_at'=>now(),
                    'updated_at'=>now(),
                    'created_at'=>now(),
                ]
            );
        }
    }

    private function capture(callable $fn): array
    {
        try {$fn(); return ['ok'=>true,'detail'=>'reachable'];}
        catch (\Throwable $e) {return ['ok'=>false,'detail'=>$e->getMessage()];}
    }
}

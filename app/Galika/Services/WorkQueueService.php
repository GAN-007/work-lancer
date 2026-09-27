<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkQueueService
{
    public function enqueue(string $kind,array $payload,string $idempotencyKey,?\DateTimeInterface $availableAt=null): void
    {
        $existing=DB::table('galika_execution_work_items')->where('idempotency_key',$idempotencyKey)->first();
        if($existing) return;

        DB::table('galika_execution_work_items')->insert([
            'correlation_id'=>(string)Str::uuid(),
            'idempotency_key'=>$idempotencyKey,
            'kind'=>$kind,
            'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),
            'status'=>'QUEUED',
            'attempts'=>0,
            'available_at'=>$availableAt??now(),
            'leased_until'=>null,
            'last_error'=>null,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }

    public function lease(int $limit=10,int $seconds=120): array
    {
        return DB::transaction(function()use($limit,$seconds){
            $rows=DB::table('galika_execution_work_items')
                ->whereIn('status',['QUEUED','RETRY'])
                ->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()))
                ->where(fn($q)=>$q->whereNull('leased_until')->orWhere('leased_until','<',now()))
                ->orderBy('created_at')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach($rows as $row){
                DB::table('galika_execution_work_items')->where('id',$row->id)->update([
                    'status'=>'LEASED',
                    'leased_until'=>now()->addSeconds($seconds),
                    'attempts'=>(int)$row->attempts+1,
                    'updated_at'=>now(),
                ]);
            }

            return $rows->map(fn($row)=>(array)$row)->all();
        });
    }

    public function complete(int $id): void
    {
        DB::table('galika_execution_work_items')->where('id',$id)->update([
            'status'=>'DONE','leased_until'=>null,'last_error'=>null,'updated_at'=>now()
        ]);
    }

    public function retry(int $id,string $error,int $attempt,?\DateTimeInterface $availableAt=null): void
    {
        DB::table('galika_execution_work_items')->where('id',$id)->update([
            'status'=>'RETRY',
            'last_error'=>$error,
            'leased_until'=>null,
            'available_at'=>$availableAt??now()->addSeconds(min(3600,30*(2**min(6,$attempt)))),
            'updated_at'=>now()
        ]);
    }
}

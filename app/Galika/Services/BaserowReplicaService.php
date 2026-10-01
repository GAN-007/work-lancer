<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class BaserowReplicaService
{
    public function __construct(private BaserowAdapter $baserow){}

    public function project(int $userId,string $kind,array $payload):void
    {
        $correlationId=(string)($payload['correlation_id']??hash('sha256',$kind.'|'.json_encode($payload)));
        $encoded=json_encode($payload,JSON_THROW_ON_ERROR);
        $now=now();

        DB::table('galika_projection_records')->updateOrInsert(
            ['user_id'=>$userId,'correlation_id'=>$correlationId],
            [
                'event_type'=>$kind,
                'payload'=>$encoded,
                'mirror_state'=>$this->baserow->configured()?'PENDING':'LOCAL_ONLY',
                'last_mirror_error'=>null,
                'updated_at'=>$now,
                'created_at'=>$now,
            ]
        );

        if(!$this->baserow->configured()) return;

        try{
            $this->baserow->upsert('Correlation ID',[
                'Correlation ID'=>$correlationId,
                'User ID'=>$userId,
                'Event Type'=>$kind,
                'Payload'=>$encoded,
                'Synced At'=>$now->toIso8601String(),
            ]);

            DB::table('galika_projection_records')
                ->where('user_id',$userId)
                ->where('correlation_id',$correlationId)
                ->update([
                    'mirror_state'=>'MIRRORED',
                    'mirrored_at'=>now(),
                    'last_mirror_error'=>null,
                    'updated_at'=>now(),
                ]);
        }catch(Throwable $e){
            DB::table('galika_projection_records')
                ->where('user_id',$userId)
                ->where('correlation_id',$correlationId)
                ->update([
                    'mirror_state'=>'FAILED',
                    'last_mirror_error'=>$e->getMessage(),
                    'updated_at'=>now(),
                ]);
            throw $e;
        }
    }
}

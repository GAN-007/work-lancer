<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\DB;

class OutboxService
{
    public function lease(int $limit=50,int $seconds=120): array
    {
        return DB::transaction(function()use($limit,$seconds){
            $rows=DB::table('galika_outbox')
                ->whereIn('status',['PENDING','RETRY'])
                ->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()))
                ->where(fn($q)=>$q->whereNull('leased_until')->orWhere('leased_until','<',now()))
                ->orderBy('id')->limit($limit)->lockForUpdate()->get();

            foreach($rows as $r){
                DB::table('galika_outbox')->where('id',$r->id)->update([
                    'status'=>'LEASED',
                    'leased_until'=>now()->addSeconds($seconds),
                    'attempts'=>(int)$r->attempts+1,
                    'updated_at'=>now()
                ]);
            }
            return $rows->map(fn($r)=>(array)$r)->all();
        });
    }

    public function done(int $id): void
    {
        DB::table('galika_outbox')->where('id',$id)->update([
            'status'=>'DONE','leased_until'=>null,'last_error'=>null,'updated_at'=>now()
        ]);
    }

    public function retry(int $id,string $error,int $attempt,int $maxAttempts=8): void
    {
        if($attempt>=$maxAttempts){
            $this->deadLetter($id,$error,$attempt);
            return;
        }

        DB::table('galika_outbox')->where('id',$id)->update([
            'status'=>'RETRY','last_error'=>$error,'leased_until'=>null,
            'available_at'=>now()->addSeconds(min(3600,30*(2**min(6,$attempt)))),
            'updated_at'=>now()
        ]);
    }

    public function deadLetter(int $id,string $error,int $attempt): void
    {
        DB::transaction(function() use($id,$error,$attempt){
            $row=DB::table('galika_outbox')->lockForUpdate()->find($id);
            if(!$row) return;

            DB::table('galika_outbox_dead_letters')->updateOrInsert(
                ['outbox_id'=>$id],
                [
                    'user_id'=>$row->user_id,
                    'destination'=>$row->destination,
                    'kind'=>$row->kind,
                    'payload'=>$row->payload,
                    'attempts'=>$attempt,
                    'last_error'=>$error,
                    'state'=>'OPEN',
                    'dead_lettered_at'=>now(),
                    'updated_at'=>now(),
                    'created_at'=>now(),
                ]
            );

            DB::table('galika_outbox')->where('id',$id)->update([
                'status'=>'DEAD','last_error'=>$error,'leased_until'=>null,'updated_at'=>now()
            ]);
        });
    }

    public function replayDeadLetter(int $deadLetterId): int
    {
        return DB::transaction(function() use($deadLetterId){
            $dead=DB::table('galika_outbox_dead_letters')->lockForUpdate()->find($deadLetterId);
            if(!$dead) throw new \RuntimeException('Dead letter not found.');
            if($dead->state==='REPLAYED') return (int)$dead->outbox_id;

            DB::table('galika_outbox')->where('id',$dead->outbox_id)->update([
                'status'=>'RETRY','attempts'=>0,'last_error'=>null,'leased_until'=>null,'available_at'=>now(),'updated_at'=>now()
            ]);
            DB::table('galika_outbox_dead_letters')->where('id',$deadLetterId)->update([
                'state'=>'REPLAYED','replayed_at'=>now(),'updated_at'=>now()
            ]);
            return (int)$dead->outbox_id;
        });
    }
}

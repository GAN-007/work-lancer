<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkQueueService{
    public function enqueue(string $kind,array $payload,string $idempotencyKey,?\DateTimeInterface $availableAt=null):void{
        DB::table('galika_execution_work_items')->updateOrInsert(['idempotency_key'=>$idempotencyKey],['correlation_id'=>(string)Str::uuid(),'kind'=>$kind,'payload'=>json_encode($payload),'status'=>'QUEUED','available_at'=>$availableAt??now(),'updated_at'=>now(),'created_at'=>now()]);
    }
    public function lease(int $limit=10,int $seconds=120):array{
        return DB::transaction(function()use($limit,$seconds){
            $rows=DB::table('galika_execution_work_items')->whereIn('status',['QUEUED','RETRY'])->where(function($q){$q->whereNull('available_at')->orWhere('available_at','<=',now());})->where(function($q){$q->whereNull('leased_until')->orWhere('leased_until','<',now());})->orderBy('created_at')->limit($limit)->lockForUpdate()->get();
            foreach($rows as $r)DB::table('galika_execution_work_items')->where('id',$r->id)->update(['status'=>'LEASED','leased_until'=>now()->addSeconds($seconds),'attempts'=>$r->attempts+1,'updated_at'=>now()]);
            return $rows->map(fn($r)=>(array)$r)->all();
        });
    }
    public function complete(int $id):void{DB::table('galika_execution_work_items')->where('id',$id)->update(['status'=>'DONE','leased_until'=>null,'updated_at'=>now()]);}
    public function retry(int $id,string $error,int $attempt):void{DB::table('galika_execution_work_items')->where('id',$id)->update(['status'=>'RETRY','last_error'=>$error,'leased_until'=>null,'available_at'=>now()->addSeconds(min(3600,30*(2**min(6,$attempt)))),'updated_at'=>now()]);}
}

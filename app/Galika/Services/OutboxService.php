<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\DB;
class OutboxService{
 public function enqueue(?int $userId,string $destination,string $operation,array $payload,string $idempotencyKey,?\DateTimeInterface $availableAt=null):void{
  DB::table('galika_outbox')->updateOrInsert(['idempotency_key'=>$idempotencyKey],['user_id'=>$userId,'destination'=>$destination,'operation'=>$operation,'payload'=>json_encode($payload,JSON_UNESCAPED_SLASHES),'status'=>'PENDING','available_at'=>$availableAt??now(),'updated_at'=>now(),'created_at'=>now()]);
 }
 public function lease(int $limit=50,int $seconds=120):array{return DB::transaction(function()use($limit,$seconds){$rows=DB::table('galika_outbox')->whereIn('status',['PENDING','RETRY'])->where('attempts','<',DB::raw('max_attempts'))->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()))->where(fn($q)=>$q->whereNull('leased_until')->orWhere('leased_until','<',now()))->orderBy('id')->limit($limit)->lockForUpdate()->get();foreach($rows as $r)DB::table('galika_outbox')->where('id',$r->id)->update(['status'=>'LEASED','leased_until'=>now()->addSeconds($seconds),'attempts'=>$r->attempts+1,'updated_at'=>now()]);return $rows->map(fn($r)=>(array)$r)->all();});}
 public function delivered(int $id):void{DB::table('galika_outbox')->where('id',$id)->update(['status'=>'DELIVERED','delivered_at'=>now(),'leased_until'=>null,'last_error'=>null,'updated_at'=>now()]);}
 public function retry(int $id,string $error,int $attempt):void{DB::table('galika_outbox')->where('id',$id)->update(['status'=>'RETRY','last_error'=>$error,'leased_until'=>null,'available_at'=>now()->addSeconds(min(3600,15*(2**min(8,$attempt)))),'updated_at'=>now()]);}
}
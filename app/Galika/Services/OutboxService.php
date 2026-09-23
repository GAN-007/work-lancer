<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\DB;
class OutboxService {
 public function lease(int $limit=25,int $seconds=180):array {
  return DB::transaction(function()use($limit,$seconds){
   $rows=DB::table('galika_outbox')->whereIn('status',['PENDING','RETRY'])->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()))->where(fn($q)=>$q->whereNull('leased_until')->orWhere('leased_until','<',now()))->orderBy('id')->limit($limit)->lockForUpdate()->get();
   foreach($rows as $r)DB::table('galika_outbox')->where('id',$r->id)->update(['status'=>'LEASED','leased_until'=>now()->addSeconds($seconds),'attempts'=>$r->attempts+1,'updated_at'=>now()]);
   return $rows->map(fn($r)=>(array)$r)->all();
  });
 }
 public function done(int $id):void {DB::table('galika_outbox')->where('id',$id)->update(['status'=>'DONE','leased_until'=>null,'updated_at'=>now()]);}
 public function retry(int $id,string $error,int $attempt):void {DB::table('galika_outbox')->where('id',$id)->update(['status'=>'RETRY','last_error'=>$error,'leased_until'=>null,'available_at'=>now()->addSeconds(min(1800,5*(2**min(8,$attempt)))),'updated_at'=>now()]);}
}
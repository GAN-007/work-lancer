<?php
namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GalikaHealthController extends Controller{
 public function __invoke(Request $request):JsonResponse{
  $expected=(string)config('services.galika.health_token');
  if($expected===''||!hash_equals($expected,(string)$request->header('X-GALIKA-HEALTH-TOKEN')))abort(403);
  $h=DB::table('galika_worker_heartbeats')->where('worker','execution')->first();
  $healthy=$h&&$h->heartbeat_at&&now()->diffInSeconds($h->heartbeat_at)<=90;
  return response()->json([
   'ok'=>(bool)$healthy,
   'worker'=>$h,
   'pending_outbox'=>DB::table('galika_outbox')->whereIn('status',['PENDING','RETRY'])->count(),
   'queued_work'=>DB::table('galika_execution_work_items')->whereIn('status',['QUEUED','RETRY'])->count()
  ],$healthy?200:503);
 }
}
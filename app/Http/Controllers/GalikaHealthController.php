<?php
namespace App\Http\Controllers;

use App\Galika\Services\RuntimeAssuranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GalikaHealthController extends Controller
{
    public function __invoke(Request $request, RuntimeAssuranceService $runtime): JsonResponse
    {
        $expected=(string)config('services.galika.health_token');
        if($expected===''||!hash_equals($expected,(string)$request->header('X-GALIKA-HEALTH-TOKEN'))) abort(403);

        $checks=$runtime->checks();
        $runtime->recordFailures($checks);
        $healthy=$runtime->overall($checks);

        return response()->json([
            'ok'=>$healthy,
            'checks'=>$checks,
            'pending_outbox'=>DB::table('galika_outbox')->whereIn('status',['PENDING','RETRY'])->count(),
            'queued_work'=>DB::table('galika_execution_work_items')->whereIn('status',['QUEUED','RETRY'])->count(),
            'failed_work'=>DB::table('galika_execution_work_items')->where('status','FAILED')->count(),
        ],$healthy?200:503);
    }
}

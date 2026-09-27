<?php
namespace App\Galika\Services;

use App\Models\GalikaBackupCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupHealthService
{
    public function snapshot(): array
    {
        $connection=DB::connection();
        $driver=$connection->getDriverName();
        $database=$connection->getDatabaseName();

        $tables=$driver==='sqlite'
            ? collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))->pluck('name')->all()
            : collect(DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public'"))->pluck('table_name')->all();

        $critical=['galika_applications','galika_execution_work_items','galika_outbox','galika_invoices','galika_payment_transactions','galika_scorecards'];
        $missing=array_values(array_filter($critical,fn($t)=>!Schema::hasTable($t)));

        $result=[
            'database'=>$database,
            'driver'=>$driver,
            'checked_at'=>now()->toIso8601String(),
            'tables'=>count($tables),
            'critical_missing'=>$missing,
            'ok'=>count($missing)===0,
        ];

        if(Schema::hasTable('galika_backup_checks')){
            GalikaBackupCheck::create([
                'kind'=>'SCHEMA_HEALTH',
                'state'=>$result['ok']?'PASSED':'FAILED',
                'database_name'=>$database,
                'table_count'=>count($tables),
                'details'=>$result,
                'checked_at'=>now(),
            ]);
        }

        return $result;
    }

    public function verifyRestoreSnapshot(string $artifactPath,string $checksum,array $details=[]): GalikaBackupCheck
    {
        if(!is_file($artifactPath)) throw new \RuntimeException('Restore verification artifact does not exist.');
        $actual=hash_file('sha256',$artifactPath);
        $state=hash_equals($checksum,$actual)?'PASSED':'FAILED';

        return GalikaBackupCheck::create([
            'kind'=>'RESTORE_VERIFICATION',
            'state'=>$state,
            'database_name'=>DB::connection()->getDatabaseName(),
            'artifact_path'=>$artifactPath,
            'checksum'=>$actual,
            'details'=>$details+['expected_checksum'=>$checksum],
            'checked_at'=>now(),
        ]);
    }
}

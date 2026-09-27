<?php
namespace App\Console\Commands;

use App\Galika\Services\BackupHealthService;
use Illuminate\Console\Command;

class GalikaBackupCheck extends Command
{
    protected $signature='galika:backup-check';
    protected $description='Persist a canonical database/backup readiness check';

    public function handle(BackupHealthService $backups): int
    {
        $result=$backups->snapshot();
        $this->line(json_encode($result,JSON_UNESCAPED_SLASHES));
        return $result['ok']?self::SUCCESS:self::FAILURE;
    }
}

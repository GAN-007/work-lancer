<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\DB;
class BackupHealthService{
 public function snapshot():array{
  return ['database'=>DB::connection()->getDatabaseName(),'checked_at'=>now()->toIso8601String(),'tables'=>collect(DB::select("SELECT name FROM sqlite_master WHERE type='table'"))->pluck('name')->count()];
 }
}

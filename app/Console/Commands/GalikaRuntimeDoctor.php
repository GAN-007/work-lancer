<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class GalikaRuntimeDoctor extends Command{
 protected $signature='galika:doctor';protected $description='Fail-fast production readiness check for the canonical GALIKA runtime';
 public function handle():int{
  $checks=[
   'db'=>fn()=>DB::select('select 1'),
   'work_queue'=>fn()=>Schema::hasTable('galika_execution_work_items')?:throw new \RuntimeException('galika_execution_work_items missing'),
   'outbox'=>fn()=>Schema::hasTable('galika_outbox')?:throw new \RuntimeException('galika_outbox missing'),
   'events'=>fn()=>Schema::hasTable('galika_career_events')?:throw new \RuntimeException('galika_career_events missing'),
  ];
  $bad=[];foreach($checks as $name=>$check){try{$check();$this->line("PASS {$name}");}catch(\Throwable $e){$bad[$name]=$e->getMessage();$this->error("FAIL {$name}: ".$e->getMessage());}}
  foreach(['APP_KEY','DATABASE_URL'] as $key){if(!env($key)){$bad[$key]='missing';$this->error("FAIL {$key}: missing");}}
  foreach(['OPENAI_API_KEY','TINYFISH_ENDPOINT'] as $key){if(!env($key))$this->warn("WARN {$key}: missing; application execution requiring it will remain queued/blocked");}
  return $bad?self::FAILURE:self::SUCCESS;
 }
}
<?php
namespace App\Console\Commands;

use App\Galika\Services\RuntimeAssuranceService;
use Illuminate\Console\Command;

class GalikaRuntimeDoctor extends Command
{
    protected $signature='galika:doctor {--require-external}';
    protected $description='Fail-fast readiness check for the PostgreSQL-authoritative GALIKA runtime';

    public function handle(RuntimeAssuranceService $runtime): int
    {
        $checks=$runtime->checks();

        foreach ($checks as $name=>$check) {
            $line=($check['ok']?'PASS ':'FAIL ').$name.' '.$check['detail'];
            $check['ok'] ? $this->line($line) : $this->error($line);
        }

        foreach (['APP_KEY','DATABASE_URL'] as $key) {
            $ok=(string)env($key)!=='';
            $checks['env:'.$key]=['ok'=>$ok,'detail'=>$ok?'configured':'missing'];
            $ok ? $this->line("PASS env:{$key}") : $this->error("FAIL env:{$key} missing");
        }

        if ($this->option('require-external')) {
            foreach (['OPENAI_API_KEY','OPEN_WEB_AGENT_ENDPOINT'] as $key) {
                $ok=(string)env($key)!=='';
                $checks['env:'.$key]=['ok'=>$ok,'detail'=>$ok?'configured':'missing'];
                $ok ? $this->line("PASS env:{$key}") : $this->error("FAIL env:{$key} missing");
            }
        }

        $runtime->recordFailures($checks);
        return $runtime->overall($checks)?self::SUCCESS:self::FAILURE;
    }
}

<?php
namespace App\Console\Commands;

use App\Galika\Services\EconomicTruthService;
use App\Models\GalikaProfile;
use Illuminate\Console\Command;

class GalikaScorecard extends Command
{
    protected $signature='galika:scorecard {--user=} {--from=} {--to=} {--currency=KES}';
    protected $description='Generate canonical wealth and career outcome scorecards from PostgreSQL evidence';

    public function handle(EconomicTruthService $truth): int
    {
        $from=$this->option('from')?new \DateTimeImmutable($this->option('from')):now()->startOfWeek()->toDateTimeImmutable();
        $to=$this->option('to')?new \DateTimeImmutable($this->option('to')):now()->endOfWeek()->toDateTimeImmutable();
        $currency=strtoupper((string)$this->option('currency'));

        $profiles=GalikaProfile::query();
        if($this->option('user')) $profiles->where('user_id',(int)$this->option('user'));

        $count=0;
        foreach($profiles->get() as $profile){
            $score=$truth->buildScorecard($profile->user_id,$from,$to,$currency);
            $this->line(json_encode([
                'user_id'=>$profile->user_id,
                'period_start'=>$score->period_start?->toDateString(),
                'period_end'=>$score->period_end?->toDateString(),
                'revenue_invoiced'=>(float)$score->revenue_invoiced,
                'cash_collected'=>(float)$score->cash_collected,
                'mrr'=>(float)$score->mrr,
                'wins'=>(int)$score->wins,
                'customers'=>(int)$score->customers,
            ],JSON_UNESCAPED_SLASHES));
            $count++;
        }

        $this->info("scorecards_generated={$count}");
        return self::SUCCESS;
    }
}

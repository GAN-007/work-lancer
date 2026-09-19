<?php
namespace App\Console\Commands;
use App\Galika\Services\CampaignExecutionService;
use App\Galika\Services\FollowUpExecutionService;
use App\Galika\Services\NextBestActionService;
use App\Galika\Services\RecruiterAcquisitionService;
use App\Models\GalikaProfile;
use Illuminate\Console\Command;
class GalikaActive extends Command{
 protected $signature='galika:active {--limit=50}';
 protected $description='Execute active career graph actions: referrals, recruiter outreach, follow-ups and next-best-actions';
 public function handle(CampaignExecutionService $campaigns,FollowUpExecutionService $followups,RecruiterAcquisitionService $recruiters,NextBestActionService $next):int{
  $r=0;GalikaProfile::where('pause_all_execution',false)->each(function($p)use(&$r,$recruiters,$next){try{$r+=$recruiters->scan($p->user_id);$next->refreshUser($p->user_id);}catch(\Throwable $e){report($e);}});
  $c=$campaigns->executeDue((int)$this->option('limit'));$f=$followups->run();
  $this->info("Recruiters {$r}; campaign actions {$c}; follow-ups {$f}");
  return self::SUCCESS;
 }
}

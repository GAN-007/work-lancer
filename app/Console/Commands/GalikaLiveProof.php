<?php
namespace App\Console\Commands;

use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\DeliveryReconciliationService;
use App\Galika\Services\RecruiterThreadService;
use App\Models\GalikaApplication;
use App\Models\GalikaCanonicalEvent;
use App\Models\GalikaOpportunity;
use Illuminate\Console\Command;

class GalikaLiveProof extends Command
{
    protected $signature='galika:prove-live {user_id} {opportunity_id} {--wait=90}';
    protected $description='Perform one explicitly selected real application and prove PostgreSQL confirmation plus Gmail reconciliation';

    public function handle(ApplicationEngine $engine, RecruiterThreadService $inbound, DeliveryReconciliationService $delivery):int
    {
        $userId=(int)$this->argument('user_id');
        $opportunity=GalikaOpportunity::findOrFail((int)$this->argument('opportunity_id'));

        if($opportunity->published_at && $opportunity->published_at->lt(now()->subHours(24))){
            $this->error('Refusing live proof: opportunity is older than 24 hours.');
            return self::FAILURE;
        }

        $application=$engine->process($opportunity,$userId)->refresh();
        if($application->status!=='SUBMITTED_CONFIRMED' || !$application->confirmation_at || !$application->confirmation_id){
            $this->error('External submission was not proven; status='.$application->status);
            return self::FAILURE;
        }

        $this->info('postgres_confirmation='.$application->confirmation_id);

        if($application->route && str_contains($application->route,'@')){
            $deliveryResult=$delivery->reconcile($application->refresh());
            $this->line('delivery_state='.($deliveryResult['state']??'UNKNOWN'));
            if(($deliveryResult['state']??null)==='HARD_BOUNCED'){
                $this->error('Email route hard-bounced; submission proof invalidated for this route.');
                return self::FAILURE;
            }
        }

        $deadline=time()+max(0,(int)$this->option('wait'));
        do {
            $processed=$inbound->scan($userId);
            $this->line('gmail_reconciled='.$processed);
            if($processed>0) break;
            if(time()<$deadline) sleep(10);
        } while(time()<$deadline);

        $application=GalikaApplication::findOrFail($application->id);
        $this->info('application_id='.$application->id.' status='.$application->status.' confirmation_at='.$application->confirmation_at?->toIso8601String());
        return self::SUCCESS;
    }
}

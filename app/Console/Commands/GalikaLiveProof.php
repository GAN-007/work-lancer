<?php
namespace App\Console\Commands;

use App\Galika\Services\ApplicationEngine;
use App\Galika\Services\DeliveryReconciliationService;
use App\Galika\Services\RecruiterThreadService;
use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use Illuminate\Console\Command;

class GalikaLiveProof extends Command
{
    protected $signature='galika:prove-live {user_id} {opportunity_id} {--wait=120}';
    protected $description='Perform one explicitly selected real application and require PostgreSQL confirmation plus reconciled external evidence';

    public function handle(ApplicationEngine $engine, RecruiterThreadService $inbound, DeliveryReconciliationService $delivery):int
    {
        $userId=(int)$this->argument('user_id');
        $opportunity=GalikaOpportunity::findOrFail((int)$this->argument('opportunity_id'));

        if($opportunity->published_at && $opportunity->published_at->lt(now()->subHours(config('galika.fresh_fallback_hours',24)))){
            $this->error('Refusing live proof: opportunity is outside the configured hard freshness limit.');
            return self::FAILURE;
        }

        $application=$engine->process($opportunity,$userId)->refresh();
        if($application->status!=='SUBMITTED_CONFIRMED' || !$application->confirmation_at || empty($application->submission_proof)){
            $this->error('External submission was not proven; status='.$application->status);
            return self::FAILURE;
        }

        $this->info('postgres_confirmation='.($application->confirmation_id?:'proof-without-provider-id'));

        if($application->route && str_contains($application->route,'@')){
            $deliveryResult=$delivery->reconcile($application->refresh());
            $this->line('delivery_state='.($deliveryResult['state']??'UNKNOWN'));
            if(in_array($deliveryResult['state']??null,['HARD_BOUNCED','PENDING'],true)){
                $this->error('Email delivery evidence is not sufficient to declare a successful live proof.');
                return self::FAILURE;
            }
        }

        $deadline=time()+max(0,(int)$this->option('wait'));
        do {
            $processed=$inbound->scan($userId);
            $this->line('gmail_reconciled='.$processed);
            $application=GalikaApplication::findOrFail($application->id);
            if($application->last_inbound_at) {
                $this->info('acknowledgement='.($application->inbound_state?:'OBSERVED'));
                return self::SUCCESS;
            }
            if(time()<$deadline) sleep(10);
        } while(time()<$deadline);

        $this->error('Submission is confirmed in PostgreSQL but no acknowledgement/recruiter message was reconciled within the proof window.');
        return self::FAILURE;
    }
}

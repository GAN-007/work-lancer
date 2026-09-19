<?php

namespace App\Services\Galika;

use App\Models\GalikaOpportunity;
use App\Models\GalikaWorkItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OpportunityPipeline
{
    public function __construct(private Canonicalizer $canonicalizer)
    {
    }

    public function ingest(array $payload): GalikaOpportunity
    {
        $canonicalKey = $this->canonicalizer->key(
            $payload['employer'],
            $payload['role_title'],
            $payload['requisition_id'] ?? null,
            $payload['authoritative_url'] ?? null,
        );

        return DB::transaction(function () use ($payload, $canonicalKey) {
            $opportunity = GalikaOpportunity::firstOrCreate(
                ['canonical_key' => $canonicalKey],
                [
                    'employer' => $payload['employer'],
                    'role_title' => $payload['role_title'],
                    'requisition_id' => $payload['requisition_id'] ?? null,
                    'source_name' => $payload['source_name'] ?? null,
                    'source_url' => $payload['source_url'] ?? null,
                    'authoritative_url' => $payload['authoritative_url'] ?? null,
                    'location_text' => $payload['location_text'] ?? null,
                    'remote_policy' => $payload['remote_policy'] ?? null,
                    'publication_timestamp' => $payload['publication_timestamp'] ?? null,
                    'discovered_at' => $payload['discovered_at'] ?? now(),
                    'application_status' => 'DISCOVERED',
                ]
            );

            if ($opportunity->wasRecentlyCreated) {
                foreach (['VERIFY', 'QUALIFY'] as $action) {
                    GalikaWorkItem::create([
                        'opportunity_id' => $opportunity->id,
                        'action' => $action,
                        'status' => 'READY',
                        'priority' => 10,
                        'available_at' => now(),
                    ]);
                }
            }

            return $opportunity;
        });
    }

    public function releaseApplyIfReady(GalikaOpportunity $opportunity): void
    {
        if ($opportunity->verification_state !== 'PASS' || $opportunity->qualification_state !== 'PASS') {
            return;
        }

        if ($opportunity->publication_timestamp) {
            $age = Carbon::parse($opportunity->publication_timestamp)->diffInMinutes(now()) / 60;
            $opportunity->freshness_hours = $age;

            if ($age > config('galika.freshness.never_apply_after_hours', 24)) {
                $opportunity->application_status = 'CLOSED';
                $opportunity->blocker_code = 'STALE';
                $opportunity->save();
                return;
            }
        }

        $opportunity->application_status = 'READY';
        $opportunity->save();

        GalikaWorkItem::firstOrCreate(
            ['opportunity_id' => $opportunity->id, 'action' => 'APPLY'],
            ['status' => 'READY', 'priority' => 20, 'available_at' => now()]
        );
    }
}

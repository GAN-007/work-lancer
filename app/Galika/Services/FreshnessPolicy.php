<?php

namespace App\Galika\Services;

use App\Galika\Domain\CanonicalRequisition;
use Carbon\CarbonImmutable;

final class FreshnessPolicy
{
    public function classify(CanonicalRequisition $job, ?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now(config('galika.timezone'));
        $age = $job->postingAgeHours($now);

        if ($age === null) {
            return 'VERIFY_REQUIRED';
        }
        if ($age < 0) {
            return 'INVALID_TIMESTAMP';
        }
        if ($age <= config('galika.freshness.primary_hours', 12)) {
            return 'PRIMARY';
        }
        if ($age <= config('galika.freshness.fallback_hours', 24)) {
            return 'FALLBACK';
        }
        return 'REJECT_TOO_OLD';
    }

    public function mayApply(CanonicalRequisition $job, bool $primaryUniverseExhausted, ?CarbonImmutable $now = null): bool
    {
        return match ($this->classify($job, $now)) {
            'PRIMARY' => true,
            'FALLBACK' => $primaryUniverseExhausted,
            default => false,
        };
    }
}

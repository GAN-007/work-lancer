<?php

namespace Tests\Unit\Galika;

use App\Galika\Domain\CanonicalRequisition;
use App\Galika\Services\FreshnessPolicy;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FreshnessPolicyTest extends TestCase
{
    public function test_primary_fallback_and_rejection_boundaries(): void
    {
        $now = CarbonImmutable::parse('2026-09-19 10:00:00', 'Africa/Nairobi');
        $policy = new FreshnessPolicy();

        $make = fn (int $hours) => new CanonicalRequisition(
            'Example', 'AI Engineer', 'https://example.com/jobs/1', '1', 'test', $now,
            $now->subHours($hours), 'Remote', 'Remote'
        );

        $this->assertSame('PRIMARY', $policy->classify($make(12), $now));
        $this->assertSame('FALLBACK', $policy->classify($make(13), $now));
        $this->assertSame('FALLBACK', $policy->classify($make(24), $now));
        $this->assertSame('REJECT_TOO_OLD', $policy->classify($make(25), $now));
        $this->assertFalse($policy->mayApply($make(13), false, $now));
        $this->assertTrue($policy->mayApply($make(13), true, $now));
        $this->assertFalse($policy->mayApply($make(25), true, $now));
    }
}

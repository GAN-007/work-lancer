<?php

namespace App\Galika\Contracts;

use App\Models\GalikaOpportunity;

interface OpportunitySource
{
    public function name(): string;

    /**
     * @return iterable<GalikaOpportunity>
     */
    public function discover(): iterable;

    public function healthCheck(): array;
}

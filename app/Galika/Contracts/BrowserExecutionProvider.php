<?php
namespace App\Galika\Contracts;

use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;

interface BrowserExecutionProvider
{
    public function apply(
        GalikaApplication $application,
        GalikaOpportunity $opportunity,
        array $candidate,
        array $answers = [],
        array $attachments = []
    ): array;
}

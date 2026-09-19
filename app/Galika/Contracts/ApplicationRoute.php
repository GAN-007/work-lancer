<?php

namespace App\Galika\Contracts;

use App\Models\ApplicationLedger;

interface ApplicationRoute
{
    public function name(): string;

    public function supports(ApplicationLedger $application): bool;

    /**
     * Return a normalized result:
     * [
     *   'ok' => bool,
     *   'terminal' => bool,
     *   'status' => string,
     *   'confirmation_id' => ?string,
     *   'evidence' => array,
     *   'failure_class' => ?string,
     *   'message' => ?string,
     * ]
     */
    public function execute(ApplicationLedger $application): array;

    public function healthCheck(): array;
}

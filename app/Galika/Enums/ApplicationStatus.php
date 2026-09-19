<?php

namespace App\Galika\Enums;

final class ApplicationStatus
{
    public const DISCOVERED = 'DISCOVERED';
    public const VERIFIED = 'VERIFIED';
    public const SUBMITTING = 'SUBMITTING';
    public const FAILED_RETRYING = 'FAILED_RETRYING';
    public const BLOCKED_REQUIRES_USER = 'BLOCKED_REQUIRES_USER';
    public const SUBMITTED_CONFIRMED = 'SUBMITTED_CONFIRMED';
    public const DUPLICATE = 'DUPLICATE';
    public const INELIGIBLE = 'INELIGIBLE';
    public const STALE = 'STALE';
    public const CLOSED = 'CLOSED';

    public const TERMINAL = [
        self::SUBMITTED_CONFIRMED,
        self::DUPLICATE,
        self::INELIGIBLE,
        self::STALE,
        self::CLOSED,
    ];
}

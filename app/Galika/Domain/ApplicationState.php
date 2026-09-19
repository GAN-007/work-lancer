<?php

namespace App\Galika\Domain;

enum ApplicationState: string
{
    case DISCOVERED = 'DISCOVERED';
    case VERIFYING = 'VERIFYING';
    case QUALIFYING = 'QUALIFYING';
    case READY = 'READY';
    case APPLYING = 'APPLYING';
    case SENT_PENDING_RECONCILIATION = 'SENT_PENDING_RECONCILIATION';
    case SUBMITTED_CONFIRMED = 'SUBMITTED_CONFIRMED';
    case BLOCKED = 'BLOCKED';
    case DUPLICATE = 'DUPLICATE';
    case INELIGIBLE = 'INELIGIBLE';
    case STALE = 'STALE';
    case CLOSED = 'CLOSED';
    case HARD_BOUNCED = 'HARD_BOUNCED';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::SUBMITTED_CONFIRMED,
            self::BLOCKED,
            self::DUPLICATE,
            self::INELIGIBLE,
            self::STALE,
            self::CLOSED,
        ], true);
    }
}

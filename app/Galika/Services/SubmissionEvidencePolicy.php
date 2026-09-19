<?php

namespace App\Galika\Services;

final class SubmissionEvidencePolicy
{
    public function emailIsConfirmed(array $evidence): bool
    {
        if (!($evidence['gmail_sent'] ?? false)) {
            return false;
        }
        if (empty($evidence['exact_recipient']) || empty($evidence['gmail_message_id'])) {
            return false;
        }
        if (($evidence['hard_bounce'] ?? false) === true) {
            return false;
        }
        return ($evidence['delivery_reconciled'] ?? false) === true;
    }

    public function browserIsConfirmed(array $evidence): bool
    {
        return !empty($evidence['confirmation_url'])
            || !empty($evidence['application_id'])
            || ($evidence['confirmation_page_observed'] ?? false) === true;
    }

    public function classifyEmail(array $evidence): string
    {
        if (($evidence['hard_bounce'] ?? false) === true) {
            return 'HARD_BOUNCED';
        }
        if ($this->emailIsConfirmed($evidence)) {
            return 'SUBMITTED_CONFIRMED';
        }
        if (($evidence['gmail_sent'] ?? false) === true) {
            return 'SENT_PENDING_RECONCILIATION';
        }
        return 'UNCONFIRMED';
    }
}

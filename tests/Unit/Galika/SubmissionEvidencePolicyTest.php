<?php

namespace Tests\Unit\Galika;

use App\Galika\Services\SubmissionEvidencePolicy;
use PHPUnit\Framework\TestCase;

class SubmissionEvidencePolicyTest extends TestCase
{
    public function test_sent_alone_is_not_confirmation_and_bounce_supersedes_sent(): void
    {
        $policy = new SubmissionEvidencePolicy();

        $sent = [
            'gmail_sent' => true,
            'exact_recipient' => 'jobs@example.com',
            'gmail_message_id' => 'abc',
            'delivery_reconciled' => false,
            'hard_bounce' => false,
        ];
        $this->assertSame('SENT_PENDING_RECONCILIATION', $policy->classifyEmail($sent));

        $confirmed = $sent;
        $confirmed['delivery_reconciled'] = true;
        $this->assertSame('SUBMITTED_CONFIRMED', $policy->classifyEmail($confirmed));

        $bounced = $confirmed;
        $bounced['hard_bounce'] = true;
        $this->assertSame('HARD_BOUNCED', $policy->classifyEmail($bounced));
        $this->assertFalse($policy->emailIsConfirmed($bounced));
    }

    public function test_browser_requires_real_confirmation_evidence(): void
    {
        $policy = new SubmissionEvidencePolicy();
        $this->assertFalse($policy->browserIsConfirmed([]));
        $this->assertTrue($policy->browserIsConfirmed(['application_id' => 'APP-123']));
    }
}

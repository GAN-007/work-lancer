<?php

return [
    'enabled' => env('GALIKA_ENABLED', true),
    'freshness' => [
        'primary_hours' => (int) env('GALIKA_DISCOVERY_MAX_AGE_HOURS', 12),
        'fallback_hours' => (int) env('GALIKA_DISCOVERY_FALLBACK_MAX_AGE_HOURS', 24),
        'never_apply_after_hours' => (int) env('GALIKA_STALE_NEVER_APPLY_HOURS', 24),
    ],
    'queue' => env('GALIKA_APPLICATION_QUEUE', 'galika'),
    'proof' => [
        'require_external_confirmation' => (bool) env('GALIKA_REQUIRE_EXTERNAL_CONFIRMATION', true),
        'email_sent_requires_reconciliation' => (bool) env('GALIKA_EMAIL_SENT_REQUIRES_RECONCILIATION', true),
    ],
    'candidate' => [
        'linkedin' => 'https://linkedin.com/in/george-nyamema-5684181',
        'portfolio' => 'https://gan-007.github.io/',
        'github' => 'https://github.com/GAN-007',
        'location' => 'Nairobi, Kenya',
    ],
];

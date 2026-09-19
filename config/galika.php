<?php

return [
    'enabled' => env('GALIKA_ENABLED', true),
    'timezone' => env('GALIKA_TIMEZONE', 'Africa/Nairobi'),
    'freshness' => [
        'primary_hours' => (int) env('GALIKA_PRIMARY_FRESHNESS_HOURS', 12),
        'fallback_hours' => (int) env('GALIKA_FALLBACK_FRESHNESS_HOURS', 24),
        'never_apply_after_hours' => (int) env('GALIKA_MAX_POSTING_AGE_HOURS', 24),
    ],
    'candidate' => [
        'remote_from_kenya' => (bool) env('GALIKA_REMOTE_FROM_KENYA', true),
        'willing_to_relocate' => (bool) env('GALIKA_WILLING_TO_RELOCATE', true),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5.6'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
    'airtable' => [
        'token' => env('AIRTABLE_TOKEN'),
        'base_id' => env('AIRTABLE_BASE_ID'),
    ],
    'tinyfish' => [
        'api_key' => env('TINYFISH_API_KEY'),
        'profile' => env('TINYFISH_BROWSER_PROFILE', 'GALIKA-JOBS'),
    ],
    'gmail' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
    ],
    'sources' => [
        'jobicy' => env('GALIKA_SOURCE_JOBICY', true),
        'joblet' => env('GALIKA_SOURCE_JOBLET', true),
        'linkedin' => env('GALIKA_SOURCE_LINKEDIN', true),
        'greenhouse' => env('GALIKA_SOURCE_GREENHOUSE', true),
        'lever' => env('GALIKA_SOURCE_LEVER', true),
        'ashby' => env('GALIKA_SOURCE_ASHBY', true),
        'workday' => env('GALIKA_SOURCE_WORKDAY', true),
        'gmail_alerts' => env('GALIKA_SOURCE_GMAIL_ALERTS', true),
    ],
];

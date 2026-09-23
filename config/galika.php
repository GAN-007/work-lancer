<?php
return [
 'minimum_match_score'=>(int)env('GALIKA_MINIMUM_MATCH_SCORE',70),
 'fresh_primary_hours'=>(int)env('GALIKA_FRESH_PRIMARY_HOURS',12),
 'fresh_fallback_hours'=>(int)env('GALIKA_FRESH_FALLBACK_HOURS',24),
 'openai'=>['model'=>env('GALIKA_OPENAI_MODEL','gpt-5.6')],
 'discovery'=>['queries'=>array_values(array_filter(array_map('trim',explode(',',env('GALIKA_DISCOVERY_QUERIES','AI Engineer,Machine Learning Engineer,Data Scientist,Data Engineer,Full Stack Developer,Backend Engineer,Technical Lead,AI Finance')))))],
 'airtable'=>['critical_path'=>false,'replica_only'=>true],
 'confirmation_required'=>true,
];
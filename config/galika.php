<?php
return [
 'minimum_match_score'=>(int)env('GALIKA_MINIMUM_MATCH_SCORE',70),
 'fresh_primary_hours'=>(int)env('GALIKA_FRESH_PRIMARY_HOURS',12),
 'fresh_fallback_hours'=>(int)env('GALIKA_FRESH_FALLBACK_HOURS',24),
 'openai'=>['model'=>env('GALIKA_OPENAI_MODEL','gpt-5.6')],
 'discovery'=>['queries'=>array_values(array_filter(array_map('trim',explode(',',env('GALIKA_DISCOVERY_QUERIES','AI Engineer,Machine Learning Engineer,Data Scientist,Data Engineer,Full Stack Developer,Backend Engineer,Technical Lead,AI Finance')))))],
 'projection'=>[
     'driver'=>'postgres',
     'baserow_mirror'=>filter_var(env('BASEROW_ENABLED',false),FILTER_VALIDATE_BOOL),
 ],
 'confirmation_required'=>true,
 'system_one'=>[
     'mode'=>env('GALIKA_SYSTEM_ONE_MODE','off'),
     'base_url'=>env('GALIKA_SYSTEM_ONE_BASE_URL',''),
     'api_key'=>env('GALIKA_SYSTEM_ONE_API_KEY',''),
     'timeout_seconds'=>(float)env('GALIKA_SYSTEM_ONE_TIMEOUT_SECONDS',1.5),
 ],
];

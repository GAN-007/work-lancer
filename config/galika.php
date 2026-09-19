<?php
return [
 'minimum_match_score'=>(int)env('GALIKA_MINIMUM_MATCH_SCORE',70),
 'max_age_days'=>(int)env('GALIKA_MAX_AGE_DAYS',30),
 'openai'=>['model'=>env('GALIKA_OPENAI_MODEL','gpt-5.6')],
 'discovery'=>['queries'=>array_values(array_filter(array_map('trim',explode(',',env('GALIKA_DISCOVERY_QUERIES','AI Engineer,Machine Learning Engineer,Data Scientist,Data Engineer,Full Stack Developer,Backend Engineer,Technical Lead,AI Finance')))))],
];

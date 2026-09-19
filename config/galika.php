<?php
return [
 'minimum_match_score'=>(int)env('GALIKA_MINIMUM_MATCH_SCORE',70),'max_age_days'=>(int)env('GALIKA_MAX_AGE_DAYS',30),'openai'=>['model'=>env('GALIKA_OPENAI_MODEL','gpt-5.6')],
 'browser'=>['endpoint'=>env('GALIKA_BROWSER_ENDPOINT'),'token'=>env('GALIKA_BROWSER_TOKEN')],
 'discovery'=>['queries'=>array_values(array_filter(array_map('trim',explode(',',env('GALIKA_DISCOVERY_QUERIES','AI Engineer,Machine Learning Engineer,Data Scientist,Data Engineer,Full Stack Developer,Backend Engineer,Technical Lead,AI Finance')))))],
 'candidate'=>['name'=>env('GALIKA_CANDIDATE_NAME','George Alfred Nyamema'),'location'=>env('GALIKA_CANDIDATE_LOCATION','Nairobi, Kenya'),'linkedin'=>env('GALIKA_LINKEDIN','https://linkedin.com/in/george-nyamema-5684181'),'portfolio'=>env('GALIKA_PORTFOLIO','https://gan-007.github.io/'),'github'=>env('GALIKA_GITHUB','https://github.com/GAN-007'),'remote_from_kenya'=>true,'willing_to_relocate'=>true,'verified_profile'=>env('GALIKA_VERIFIED_PROFILE','')],
];

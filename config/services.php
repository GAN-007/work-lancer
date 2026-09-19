<?php
return [
 'mailgun'=>['domain'=>env('MAILGUN_DOMAIN'),'secret'=>env('MAILGUN_SECRET'),'endpoint'=>env('MAILGUN_ENDPOINT','api.mailgun.net'),'scheme'=>'https'],
 'postmark'=>['token'=>env('POSTMARK_TOKEN')],
 'ses'=>['key'=>env('AWS_ACCESS_KEY_ID'),'secret'=>env('AWS_SECRET_ACCESS_KEY'),'region'=>env('AWS_DEFAULT_REGION','us-east-1')],
 'openai'=>['key'=>env('OPENAI_API_KEY')],
 'gmail'=>['access_token'=>env('GMAIL_ACCESS_TOKEN'),'from'=>env('GMAIL_FROM')],
 'airtable'=>['token'=>env('AIRTABLE_TOKEN'),'base_id'=>env('AIRTABLE_BASE_ID')],
 'tinyfish'=>['key'=>env('TINYFISH_API_KEY'),'endpoint'=>env('TINYFISH_ENDPOINT')],
 'jobicy'=>['endpoint'=>env('JOBICY_ENDPOINT','https://jobicy.com/api/v2/remote-jobs')],
];

<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GalikaIntelligenceService
{
    public function __construct(private ConnectionVault $vault){}

    private function key(int $userId):string
    {
        try{return $this->vault->credential($userId,'openai','api_key');}
        catch(\Throwable $e){
            $key=config('services.openai.key');
            if(!$key) throw new RuntimeException('OpenAI API key missing');
            return $key;
        }
    }

    public function structured(int $userId,string $prompt,string $name,array $schema):array
    {
        $r=Http::withToken($this->key($userId))->timeout(90)->post('https://api.openai.com/v1/responses',[
            'model'=>config('galika.openai.model','gpt-5.6'),
            'input'=>$prompt,
            'text'=>['format'=>['type'=>'json_schema','name'=>$name,'strict'=>true,'schema'=>$schema]],
        ]);
        if(!$r->successful()) throw new RuntimeException("OpenAI {$name} failed: ".$r->body());
        return json_decode(data_get($r->json(),'output.0.content.0.text'),true,512,JSON_THROW_ON_ERROR);
    }

    public function extractCvEvidence(int $userId,string $text):array
    {
        $schema=['type'=>'object','properties'=>['facts'=>['type'=>'array','items'=>[
            'type'=>'object','properties'=>[
                'domain'=>['type'=>'string'],'fact'=>['type'=>'string'],'confidence'=>['type'=>'number'],
                'tags'=>['type'=>'array','items'=>['type'=>'string']]
            ],'required'=>['domain','fact','confidence','tags'],'additionalProperties'=>false
        ]]],'required'=>['facts'],'additionalProperties'=>false];

        $r=$this->structured($userId,
            "Extract only explicit career evidence from this CV. Do not infer unmentioned skills, dates, credentials, authorization, or experience. Text: ".$text,
            'cv_evidence',$schema);
        return $r['facts'];
    }

    public function applicationPack(int $userId,array $evidence,array $job):array
    {
        $schema=['type'=>'object','properties'=>[
            'resume'=>['type'=>'string'],'cover_letter'=>['type'=>'string']
        ],'required'=>['resume','cover_letter'],'additionalProperties'=>false];

        return $this->structured($userId,
            "Create a tailored ATS-friendly resume and concise cover letter using ONLY the verified evidence. Never invent metrics, employers, dates, qualifications, or technologies. Evidence: ".json_encode($evidence)." Job: ".json_encode($job),
            'application_pack',$schema);
    }

    public function classifyRecruiter(int $userId,array $job,string $subject,string $from,string $body):array
    {
        $schema=['type'=>'object','properties'=>[
            'classification'=>['type'=>'string','enum'=>['ACKNOWLEDGED','ASSESSMENT','INTERVIEW','OFFER','REJECTION','INFO_REQUEST','RECRUITER_REPLY','OTHER']],
            'requires_human'=>['type'=>'boolean'],'reason'=>['type'=>'string']
        ],'required'=>['classification','requires_human','reason'],'additionalProperties'=>false];

        return $this->structured($userId,
            "Classify this recruiting email. Mark requires_human=true for interview scheduling choices, offers, salary, work authorization, legal declarations, medical/EEO, binding availability, or any material unknown. Job: ".json_encode($job)." Subject: {$subject} From: {$from} Body: {$body}",
            'recruiter_message',$schema);
    }

    public function safeRecruiterReply(int $userId,array $job,string $classification,string $body):array
    {
        $schema=['type'=>'object','properties'=>[
            'needs_user'=>['type'=>'boolean'],'reply'=>['type'=>'string'],'reason'=>['type'=>'string']
        ],'required'=>['needs_user','reply','reason'],'additionalProperties'=>false];

        return $this->structured($userId,
            "Draft a brief professional reply as the candidate. Do not make salary, legal, authorization, relocation, medical/EEO, interview-time, or binding commitments unless explicitly known. If a material decision is required, set needs_user=true and leave reply empty. Job: ".json_encode($job)." Classification: {$classification} Message: {$body}",
            'recruiter_reply',$schema);
    }

    public function wealthPlan(int $userId,array $profile,array $item):array
    {
        $schema=['type'=>'object','properties'=>[
            'viable'=>['type'=>'boolean'],'strategy'=>['type'=>'string'],'next_action'=>['type'=>'string'],
            'requires_human'=>['type'=>'boolean'],'reason'=>['type'=>'string']
        ],'required'=>['viable','strategy','next_action','requires_human','reason'],'additionalProperties'=>false];

        return $this->structured($userId,
            "Evaluate this non-job wealth opportunity for a professional user. Produce an evidence-grounded execution strategy for consulting, B2B, tender, partnership, product/IP or other revenue work. Do not make binding commitments or invent capabilities. Profile: ".json_encode($profile)." Item: ".json_encode($item),
            'wealth_plan',$schema);
    }
}

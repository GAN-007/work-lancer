<?php
namespace App\Galika\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class GalikaAIService {
 public function analyze(array $candidate,array $job): array {
  $key=config('services.openai.key'); if(!$key) throw new RuntimeException('OPENAI_API_KEY missing');
  $schema=['type'=>'object','properties'=>['eligible'=>['type'=>'boolean'],'score'=>['type'=>'integer','minimum'=>0,'maximum'=>100],'reason'=>['type'=>'string'],'unknown_material_questions'=>['type'=>'array','items'=>['type'=>'string']],'positioning'=>['type'=>'string']],'required'=>['eligible','score','reason','unknown_material_questions','positioning'],'additionalProperties'=>false];
  $prompt="Evaluate this job against verified candidate evidence. Never infer or invent authorization, sponsorship, salary, medical/EEO, certifications, degrees or experience. Candidate: ".json_encode($candidate)." Job: ".json_encode($job);
  $r=Http::withToken($key)->timeout(60)->post('https://api.openai.com/v1/responses',['model'=>config('galika.openai.model','gpt-5.6'),'input'=>$prompt,'text'=>['format'=>['type'=>'json_schema','name'=>'qualification','strict'=>true,'schema'=>$schema]]]);
  if(!$r->successful()) throw new RuntimeException('OpenAI qualification failed: '.$r->body());
  $text=data_get($r->json(),'output.0.content.0.text'); return json_decode($text,true,512,JSON_THROW_ON_ERROR);
 }
 public function answer(array $candidate,string $question,string $jobContext): array {
  $key=config('services.openai.key');
  $prompt="Write a concise natural job-application answer using ONLY the verified candidate facts. If the question requires an unknown material fact, return needs_user=true and do not guess. Candidate: ".json_encode($candidate)." Question: {$question} Job: {$jobContext}";
  $schema=['type'=>'object','properties'=>['needs_user'=>['type'=>'boolean'],'answer'=>['type'=>'string'],'source_basis'=>['type'=>'string']],'required'=>['needs_user','answer','source_basis'],'additionalProperties'=>false];
  $r=Http::withToken($key)->timeout(60)->post('https://api.openai.com/v1/responses',['model'=>config('galika.openai.model','gpt-5.6'),'input'=>$prompt,'text'=>['format'=>['type'=>'json_schema','name'=>'answer','strict'=>true,'schema'=>$schema]]]);
  if(!$r->successful()) throw new RuntimeException('OpenAI answer failed: '.$r->body()); return json_decode(data_get($r->json(),'output.0.content.0.text'),true,512,JSON_THROW_ON_ERROR);
 }
}

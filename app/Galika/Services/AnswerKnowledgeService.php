<?php
namespace App\Galika\Services;

use App\Models\GalikaAnswerKnowledge;
use Illuminate\Support\Str;

class AnswerKnowledgeService
{
 public function intent(string $question):string
 {
  $q=mb_strtolower(trim($question));
  foreach([
   'WORK_AUTHORIZATION'=>['authorized to work','work authorization','legally authorized'],
   'SPONSORSHIP'=>['sponsorship','visa sponsorship'],
   'RELOCATION'=>['relocate','relocation'],
   'SALARY'=>['salary','compensation','pay expectation'],
   'NOTICE_PERIOD'=>['notice period','start date','available to start'],
  ] as $intent=>$needles)foreach($needles as $n)if(str_contains($q,$n))return $intent;
  return Str::slug(Str::limit($q,80,''),'_');
 }
 public function remember(int $userId,string $question,string $answer,bool $material=true,array $provenance=[]):GalikaAnswerKnowledge
 {
  $intent=$this->intent($question);
  return GalikaAnswerKnowledge::updateOrCreate(['user_id'=>$userId,'intent'=>$intent,'scope'=>'GLOBAL'],[
   'canonical_question'=>$question,'answer'=>$answer,'material'=>$material,'provenance'=>$provenance
  ]);
 }
 public function answer(int $userId,string $question):?string
 {
  $intent=$this->intent($question);
  $row=GalikaAnswerKnowledge::where(['user_id'=>$userId,'intent'=>$intent])->where(function($q){$q->whereNull('valid_until')->orWhere('valid_until','>',now());})->latest()->first();
  return $row?->answer;
 }
}

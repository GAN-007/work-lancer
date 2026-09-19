<?php
namespace App\Galika\Services;

use App\Models\GalikaEmployer;
use App\Models\GalikaOpportunity;

class OpportunityTrustService
{
 public function assess(GalikaOpportunity $o):array
 {
  $score=100;$reasons=[];$url=parse_url((string)$o->url);$host=mb_strtolower($url['host']??'');
  $text=mb_strtolower(($o->title??'').' '.($o->description??''));
  foreach(['pay a fee','gift card','crypto','telegram only','equipment purchase','western union'] as $flag){
   if(str_contains($text,$flag)){$score-=35;$reasons[]='FLAG_'.$flag;}
  }
  if(!$host){$score-=20;$reasons[]='NO_DOMAIN';}
  if($o->source_authority==='AGGREGATOR'){$score-=5;$reasons[]='AGGREGATOR_ONLY';}
  $state=$score>=80?'TRUSTED':($score>=55?'REVIEW':'HIGH_RISK');
  $o->update(['trust_state'=>$state,'evidence'=>array_merge($o->evidence??[],['trust'=>['score'=>$score,'reasons'=>$reasons]])]);
  if($o->employer_id)GalikaEmployer::whereKey($o->employer_id)->update(['trust'=>['score'=>$score,'reasons'=>$reasons,'state'=>$state]]);
  return compact('score','reasons','state');
 }
}

<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;
class AtsRouter {
 public function detect(GalikaOpportunity $o):string {
  $url=mb_strtolower((string)($o->official_url?:$o->url));
  return match(true){
   str_contains($url,'greenhouse.io')||str_contains($url,'boards.greenhouse')=>'GREENHOUSE',
   str_contains($url,'lever.co')=>'LEVER',
   str_contains($url,'ashbyhq.com')=>'ASHBY',
   str_contains($url,'myworkdayjobs.com')||str_contains($url,'workday.com')=>'WORKDAY',
   str_contains($url,'linkedin.com')=>'LINKEDIN',
   str_contains($url,'ceipal.com')||str_contains($url,'ceipal')=>'CEIPAL',
   str_contains($url,'solvo')=>'SOLVO',
   str_contains($url,'smartrecruiters.com')=>'SMARTRECRUITERS',
   str_contains($url,'jobvite.com')=>'JOBVITE',
   str_contains($url,'icims.com')=>'ICIMS',
   str_contains($url,'successfactors')=>'SUCCESSFACTORS',
   default=>'EMPLOYER_FORM',
  };
 }
 public function browserFirstClass(string $type):bool{return in_array($type,['GREENHOUSE','LEVER','ASHBY','WORKDAY','LINKEDIN','CEIPAL','SOLVO','SMARTRECRUITERS','JOBVITE','ICIMS','SUCCESSFACTORS','EMPLOYER_FORM'],true);}
}
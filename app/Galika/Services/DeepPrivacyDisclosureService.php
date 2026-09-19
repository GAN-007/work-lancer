<?php
namespace App\Galika\Services;
use App\Models\GalikaPrivacyRule;
class DeepPrivacyDisclosureService{
 public function allowed(int $userId,string $path,string $stage):bool{
  $rule=GalikaPrivacyRule::where('user_id',$userId)->where('data_type',$path)->where('stage',$stage)->first();
  if($rule)return (bool)$rule->allowed;
  foreach(['passport','national_id','medical','criminal','salary_history','references','home_address'] as $s)if(str_contains($path,$s))return false;
  return true;
 }
 public function filter(int $userId,array $payload,string $stage,string $prefix=''):array{
  $out=[];
  foreach($payload as $k=>$v){
   $path=$prefix===''?(string)$k:$prefix.'.'.$k;
   if(is_array($v)){$nested=$this->filter($userId,$v,$stage,$path);if($nested!==[])$out[$k]=$nested;continue;}
   if($this->allowed($userId,$path,$stage))$out[$k]=$v;
  }return $out;
 }
}

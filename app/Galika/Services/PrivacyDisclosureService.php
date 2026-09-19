<?php
namespace App\Galika\Services;
use App\Models\GalikaPrivacyRule;
class PrivacyDisclosureService{
 public function allowed(int $userId,string $dataType,string $stage):bool{
  $rule=GalikaPrivacyRule::where(['user_id'=>$userId,'data_type'=>$dataType,'stage'=>$stage])->first();
  if($rule)return (bool)$rule->allowed;
  return in_array($dataType,['name','email','phone','career_evidence'],true)&&in_array($stage,['APPLICATION','RECRUITER'],true);
 }
 public function filter(int $userId,array $payload,string $stage):array{
  return collect($payload)->filter(fn($v,$k)=>$this->allowed($userId,(string)$k,$stage))->all();
 }
}

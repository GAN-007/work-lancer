<?php
namespace App\Galika\Services;

use App\Models\GalikaProfile;
use App\Models\GalikaWorkAuthorization;

class LocationFeasibilityService
{
 public function evaluate(int $userId,array $job):array
 {
  $profile=GalikaProfile::firstWhere('user_id',$userId);
  $loc=mb_strtolower((string)($job['location']??''));
  if($loc===''||str_contains($loc,'worldwide')||str_contains($loc,'global'))return ['feasible'=>true,'reason'=>'GLOBAL'];
  if(str_contains($loc,'remote')){
   foreach(['us only','usa only','united states only','eu only','uk only','europe only'] as $restriction){
    if(str_contains($loc,$restriction))return ['feasible'=>false,'reason'=>'REMOTE_RESTRICTED'];
   }
   return ['feasible'=>true,'reason'=>'REMOTE'];
  }
  $country=$this->countryFromLocation($loc);
  if(!$country)return ['feasible'=>true,'reason'=>'UNKNOWN_GEOGRAPHY'];
  $auth=GalikaWorkAuthorization::where(['user_id'=>$userId,'country'=>$country])->first();
  if($auth&&in_array($auth->status,['CITIZEN','AUTHORIZED'],true))return ['feasible'=>true,'reason'=>'AUTHORIZED'];
  if($auth&&($auth->remote_contractor_ok||$auth->eor_ok||$auth->willing_to_relocate))return ['feasible'=>true,'reason'=>'ALTERNATE_PATH'];
  if($profile?->willing_to_relocate)return ['feasible'=>true,'reason'=>'RELOCATION_WILLING'];
  return ['feasible'=>false,'reason'=>'LOCATION_UNRESOLVED'];
 }
 private function countryFromLocation(string $loc):?string
 {
  $map=['kenya'=>'KE','nairobi'=>'KE','united states'=>'US','usa'=>'US','london'=>'GB','united kingdom'=>'GB','uk'=>'GB','germany'=>'DE','canada'=>'CA','france'=>'FR'];
  foreach($map as $needle=>$code)if(str_contains($loc,$needle))return $code;
  return null;
 }
}

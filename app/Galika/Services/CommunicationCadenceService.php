<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use Carbon\CarbonInterface;

class CommunicationCadenceService
{
 public function next(GalikaApplication $a,int $touches=0):?CarbonInterface
 {
  if($a->delivery_state==='SENT_PENDING')return null;
  if($a->delivery_state==='HARD_BOUNCED')return null;
  if(in_array($a->inbound_state,['INTERVIEW','OFFER','REJECTION'],true))return null;
  $days=match(true){$touches===0=>5,$touches===1=>12,$touches===2=>21,default=>45};
  return now()->addWeekdays($days);
 }
}

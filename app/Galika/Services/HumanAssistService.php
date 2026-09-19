<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaHumanAssist;
class HumanAssistService{
 public function create(GalikaApplication $a,string $kind,string $instructions,array $context=[]):GalikaHumanAssist{
  return GalikaHumanAssist::firstOrCreate(['user_id'=>$a->user_id,'application_id'=>$a->id,'kind'=>$kind,'state'=>'OPEN'],['instructions'=>$instructions,'context'=>$context]);
 }
}

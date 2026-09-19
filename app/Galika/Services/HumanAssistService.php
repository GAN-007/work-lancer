<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaHumanAssist;use Illuminate\Support\Str;
class HumanAssistService{
 public function create(GalikaApplication $a,string $kind,string $instructions,array $context=[]):GalikaHumanAssist{
  return GalikaHumanAssist::firstOrCreate(
   ['user_id'=>$a->user_id,'application_id'=>$a->id,'kind'=>$kind,'state'=>'OPEN'],
   ['instructions'=>$instructions,'context'=>$context,'resume_token'=>(string)Str::uuid(),'expires_at'=>now()->addHours(6)]
  );
 }
 public function resume(GalikaHumanAssist $assist):GalikaHumanAssist{
  if($assist->state!=='OPEN')return $assist;
  if($assist->expires_at&&$assist->expires_at->isPast()){$assist->update(['state'=>'EXPIRED']);return $assist->refresh();}
  $assist->update(['state'=>'RESUMED','resumed_at'=>now()]);
  return $assist->refresh();
 }
}

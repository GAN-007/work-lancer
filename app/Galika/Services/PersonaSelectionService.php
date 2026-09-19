<?php
namespace App\Galika\Services;
use App\Models\GalikaPersona;
use App\Models\GalikaOpportunity;
class PersonaSelectionService{
 public function select(int $userId,GalikaOpportunity $o):?GalikaPersona{
  $title=mb_strtolower($o->title);$best=null;$bestScore=-INF;
  foreach(GalikaPersona::where('user_id',$userId)->where('active',true)->get() as $p){
   $score=(float)($p->performance_score??0);
   foreach($p->target_roles??[] as $role)if(str_contains($title,mb_strtolower($role)))$score+=10;
   if($score>$bestScore){$bestScore=$score;$best=$p;}
  }return $best;
 }
}

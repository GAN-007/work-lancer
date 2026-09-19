<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;use App\Models\GalikaDocumentVariant;use App\Models\GalikaPersona;
class AdaptiveLearningService{
 public function updateForApplication(GalikaApplication $a,string $event):void{
  $source=$a->source?:$a->opportunity?->source;if($source)app(SourceMetricService::class)->bump($source,$event);
  if($a->persona_id){
   $p=GalikaPersona::find($a->persona_id);if($p){
    $apps=GalikaApplication::where('persona_id',$p->id)->count();$responses=GalikaApplication::where('persona_id',$p->id)->whereNotNull('last_inbound_at')->count();$interviews=GalikaApplication::where('persona_id',$p->id)->where('inbound_state','INTERVIEW')->count();$offers=GalikaApplication::where('persona_id',$p->id)->where('inbound_state','OFFER')->count();
    $score=$apps?($responses+$interviews*3+$offers*8)/$apps:0;$p->update(['performance_score'=>$score]);
   }
  }
 }
}

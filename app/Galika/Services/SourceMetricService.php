<?php
namespace App\Galika\Services;
use App\Models\GalikaSourceMetric;
class SourceMetricService{
 public function bump(string $source,string $event,int $by=1):void{
  $m=GalikaSourceMetric::firstOrCreate(['source'=>$source]);
  $field=match($event){'discovered'=>'discovered','submitted'=>'submitted','response'=>'responses','interview'=>'interviews','offer'=>'offers',default=>null};
  if($field)$m->increment($field,$by);
  $m->update(['last_seen_at'=>now()]);
  $this->recompute($m);
 }
 public function priority(string $source):float{return (float)(GalikaSourceMetric::where('source',$source)->value('yield_score')??0);}
 private function recompute(GalikaSourceMetric $m):void{
  $d=max(1,$m->discovered);$s=max(1,$m->submitted);
  $score=(($m->responses/$s)*1)+(($m->interviews/$s)*3)+(($m->offers/$s)*8);
  $m->update(['yield_score'=>round($score,4)]);
 }
}

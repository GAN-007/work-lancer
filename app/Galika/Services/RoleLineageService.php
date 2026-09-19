<?php
namespace App\Galika\Services;
use App\Models\GalikaOpportunity;use App\Models\GalikaRoleLineage;
class RoleLineageService{
 public function observe(GalikaOpportunity $o,array $snapshot):GalikaRoleLineage{
  $lineage=$o->lineage_key?:hash('sha256',mb_strtolower(trim($o->employer)).'|'.mb_strtolower(trim($o->title)));
  $version=hash('sha256',json_encode([$snapshot['title']??$o->title,$snapshot['location']??$o->location,$snapshot['description']??$o->description,$snapshot['requisition_id']??$o->requisition_id]));
  $o->update(['lineage_key'=>$lineage]);
  return GalikaRoleLineage::firstOrCreate(['opportunity_id'=>$o->id,'version_hash'=>$version],['lineage_key'=>$lineage,'state'=>'ACTIVE','snapshot'=>$snapshot,'observed_at'=>now()]);
 }
}

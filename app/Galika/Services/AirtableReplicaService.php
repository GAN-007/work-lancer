<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\DB;
class AirtableReplicaService{
 public function __construct(private AirtableAdapter $airtable){}
 public function syncApplication(int $userId,int $applicationId):void{
  $a=GalikaApplication::with('opportunity')->findOrFail($applicationId);$o=$a->opportunity;
  $this->airtable->upsert($userId,config('galika.airtable.application_table','Applications'),'Application Key',[
   'Application Key'=>$a->application_key,'Status'=>$a->status,'Employer'=>$o?->employer,'Role'=>$o?->title,'URL'=>$o?->official_url?:$o?->url,'ATS'=>$a->ats_type,'Submitted At'=>optional($a->submitted_at)?->toIso8601String(),'Confirmation ID'=>$a->confirmation_id,'Delivery State'=>$a->delivery_state,'Failure Class'=>$a->failure_class
  ]);
 }
 public function syncOpportunity(int $userId,int $opportunityId):void{
  $o=GalikaOpportunity::findOrFail($opportunityId);
  $this->airtable->upsert($userId,config('galika.airtable.opportunity_table','Opportunities'),'Canonical Key',['Canonical Key'=>$o->canonical_key,'Employer'=>$o->employer,'Role'=>$o->title,'URL'=>$o->official_url?:$o->url,'Source'=>$o->source,'Eligibility'=>$o->eligibility,'Match Score'=>$o->match_score]);
 }
}
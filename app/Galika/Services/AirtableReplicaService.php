<?php
namespace App\Galika\Services;
use App\Models\GalikaApplication;
class AirtableReplicaService {
 public function __construct(private AirtableAdapter $airtable){}
 public function syncApplication(int $userId,int $applicationId):void {
  $a=GalikaApplication::with('opportunity')->findOrFail($applicationId);
  $this->airtable->upsert($userId,'Application Ledger','Application Key',[
   'Application Key'=>$a->application_key,'Employer'=>$a->opportunity?->company,'Role'=>$a->opportunity?->title,'Status'=>$a->status,
   'Canonical Requisition'=>$a->ats_requisition_id,'Submitted At'=>$a->submitted_at?->toIso8601String(),'Confirmation At'=>$a->confirmation_at?->toIso8601String(),
   'Postgres Application ID'=>(string)$a->id,'Replica Synced At'=>now()->toIso8601String()
  ]);
 }
}
<?php
namespace App\Galika\Services;
use App\Models\GalikaPerson;use App\Models\GalikaRelationship;use App\Models\GalikaEmployer;
class RecruiterAcquisitionService{
 public function __construct(private GmailAdapter $gmail){}
 public function scan(int $userId):int{
  $count=0;
  foreach($this->gmail->search($userId,'newer_than:365d (recruiter OR recruiting OR talent OR careers OR hiring)',300) as $row){
   $m=$this->gmail->read($userId,$row['id']);$from=$this->gmail->header($m,'From')??'';$body=$this->gmail->body($m);
   if(!preg_match('/([^<"]+)\s*<([^>]+)>/',$from,$x)){continue;}
   $name=trim($x[1]);$email=trim($x[2]);$domain=substr(strrchr($email,'@')?:'',1);if(!$domain)continue;
   $employer=GalikaEmployer::firstOrCreate(['domain'=>$domain],['canonical_name'=>ucfirst(explode('.',$domain)[0])]);
   $person=GalikaPerson::firstOrCreate(['email'=>$email],['employer_id'=>$employer->id,'name'=>$name?:$email,'role'=>'Recruiter','relationship_type'=>'RECRUITER','metadata'=>['source'=>'GMAIL_HISTORY']]);
   if(!$person->employer_id)$person->update(['employer_id'=>$employer->id]);
   GalikaRelationship::firstOrCreate(['user_id'=>$userId,'person_id'=>$person->id],['strength'=>'MEDIUM','state'=>'KNOWN','evidence'=>['source'=>'GMAIL_HISTORY','message_id'=>$row['id']]]);
   $count++;
  }return $count;
 }
}

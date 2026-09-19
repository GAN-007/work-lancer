<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaEmployer;
use App\Models\GalikaOutcome;

class EmployerMemoryService
{
 public function refresh(int $userId,GalikaEmployer $employer):array
 {
  $apps=GalikaApplication::where('user_id',$userId)->whereHas('opportunity',fn($q)=>$q->where('employer_id',$employer->id))->get();
  $outcomes=GalikaOutcome::whereIn('application_id',$apps->pluck('id'))->get();
  $memory=[
   'applications'=>$apps->count(),
   'confirmed'=>$apps->where('status','SUBMITTED_CONFIRMED')->count(),
   'interviews'=>$outcomes->where('type','INTERVIEW')->count(),
   'offers'=>$outcomes->where('type','OFFER')->count(),
   'rejections'=>$outcomes->where('type','REJECTION')->count(),
   'last_application_at'=>$apps->max('created_at')?->toIso8601String(),
  ];
  $employer->update(['memory'=>$memory]);
  return $memory;
 }
}

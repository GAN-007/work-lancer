<?php
namespace App\Galika\Services;
use App\Models\GalikaInterview;use App\Models\GalikaNextAction;
class InterviewOrchestrationService{
 public function prepare(GalikaInterview $i):array{
  $a=$i->application()->with('opportunity')->first();
  $job=$a?->opportunity;
  $plan=[
   'status'=>'READY',
   'company_research'=>['employer'=>$job?->employer,'role'=>$job?->title],
   'focus'=>['role_requirements','verified_evidence','STAR_examples','technical_topics','questions_for_interviewer'],
   'reminders'=>['review_job_description','prepare_3_STAR_examples','test_meeting_link','prepare_questions'],
  ];
  $i->update(['prep_plan'=>$plan]);
  GalikaNextAction::updateOrCreate(['user_id'=>$a->user_id,'subject_type'=>'INTERVIEW','subject_id'=>$i->id,'state'=>'PENDING'],['action'=>'COMPLETE_INTERVIEW_PREP','priority'=>'HIGH','reason'=>['stage'=>$i->stage],'due_at'=>$i->starts_at?->copy()->subHours(24)??now()]);
  return $plan;
 }
}

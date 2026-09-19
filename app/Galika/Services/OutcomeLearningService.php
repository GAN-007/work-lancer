<?php
namespace App\Galika\Services;

use App\Models\GalikaApplication;
use App\Models\GalikaLearningLog;
use App\Models\GalikaOutcome;

class OutcomeLearningService
{
 public function record(GalikaApplication $a,string $type,?string $stage=null,?string $reason=null,array $evidence=[]):GalikaOutcome
 {
  $o=GalikaOutcome::create(['application_id'=>$a->id,'type'=>$type,'stage'=>$stage,'reason'=>$reason,'evidence'=>$evidence,'occurred_at'=>now()]);
  GalikaLearningLog::create(['user_id'=>$a->user_id,'kind'=>'OUTCOME','subject'=>$type,'evidence'=>['application_id'=>$a->id,'stage'=>$stage,'reason'=>$reason]+$evidence,'created_at'=>now(),'updated_at'=>now()]);
  return $o;
 }
}

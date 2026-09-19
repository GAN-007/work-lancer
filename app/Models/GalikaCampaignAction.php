<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class GalikaCampaignAction extends Model{protected $guarded=[];protected $casts=['scheduled_at'=>'datetime','executed_at'=>'datetime','proof'=>'array'];public function campaign(){return $this->belongsTo(GalikaCampaign::class,'campaign_id');}public function person(){return $this->belongsTo(GalikaPerson::class,'person_id');}}

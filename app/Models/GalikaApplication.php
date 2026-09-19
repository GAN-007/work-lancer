<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaApplication extends Model { protected $table='galika_applications'; protected $guarded=[]; protected $casts=['started_at'=>'datetime','submitted_at'=>'datetime','confirmation_at'=>'datetime','delivery_reconciled_at'=>'datetime']; public function opportunity(){return $this->belongsTo(GalikaOpportunity::class,'opportunity_id');} public function answers(){return $this->hasMany(GalikaApplicationAnswer::class,'application_id');} public function decisions(){return $this->hasMany(GalikaDecision::class,'application_id');} }

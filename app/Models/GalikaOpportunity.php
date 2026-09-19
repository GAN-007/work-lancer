<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaOpportunity extends Model { protected $table='galika_opportunities'; protected $guarded=[]; protected $casts=['published_at'=>'datetime','discovered_at'=>'datetime','verified_at'=>'datetime','evidence'=>'array']; public function application(){return $this->hasOne(GalikaApplication::class,'opportunity_id');} }

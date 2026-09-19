<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaPerson extends Model{protected $guarded=[];protected $casts=['metadata'=>'array'];public function employer(){return $this->belongsTo(GalikaEmployer::class,'employer_id');}}

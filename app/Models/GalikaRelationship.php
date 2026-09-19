<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaRelationship extends Model{protected $guarded=[];protected $casts=['last_contact_at'=>'datetime','evidence'=>'array'];public function person(){return $this->belongsTo(GalikaPerson::class,'person_id');}}

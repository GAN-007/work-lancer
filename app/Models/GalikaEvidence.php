<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaEvidence extends Model{protected $table='galika_evidence';protected $guarded=[];protected $casts=['verified'=>'boolean','tags'=>'array'];}

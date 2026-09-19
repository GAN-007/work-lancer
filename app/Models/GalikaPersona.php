<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaPersona extends Model{protected $guarded=[];protected $casts=['target_roles'=>'array','emphasis'=>'array','active'=>'boolean'];}

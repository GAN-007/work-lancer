<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaIntegration extends Model{protected $guarded=[];protected $casts=['last_health_check'=>'datetime','last_success'=>'datetime','capabilities'=>'array'];}

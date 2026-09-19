<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaEvent extends Model{protected $guarded=[];protected $casts=['starts_at'=>'datetime','companies'=>'array','people'=>'array','notes'=>'array'];}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaInterview extends Model{protected $guarded=[];protected $casts=['starts_at'=>'datetime','interviewers'=>'array','prep_plan'=>'array','outcome'=>'array'];}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaOutcome extends Model{protected $guarded=[];protected $casts=['evidence'=>'array','occurred_at'=>'datetime'];}

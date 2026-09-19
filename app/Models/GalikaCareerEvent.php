<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class GalikaCareerEvent extends Model{protected $guarded=[];protected $casts=['evidence'=>'array','occurred_at'=>'datetime'];}

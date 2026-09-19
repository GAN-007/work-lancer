<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaProfile extends Model{protected $guarded=[];protected $casts=['remote_from_home_country'=>'boolean','willing_to_relocate'=>'boolean','autonomous_apply_enabled'=>'boolean','pause_all_execution'=>'boolean'];public function user(){return $this->belongsTo(User::class);}}

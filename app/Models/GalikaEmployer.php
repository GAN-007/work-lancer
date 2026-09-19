<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaEmployer extends Model{protected $guarded=[];protected $casts=['memory'=>'array','trust'=>'array','cooldown_until'=>'datetime'];}

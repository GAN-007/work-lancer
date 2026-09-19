<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class GalikaRoleLineage extends Model{protected $guarded=[];protected $casts=['snapshot'=>'array','observed_at'=>'datetime'];}

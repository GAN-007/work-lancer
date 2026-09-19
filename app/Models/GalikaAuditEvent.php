<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaAuditEvent extends Model{protected $guarded=[];protected $casts=['before'=>'array','after'=>'array','context'=>'array'];}

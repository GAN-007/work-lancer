<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaDeliveryEvent extends Model{protected $guarded=[];protected $casts=['event_at'=>'datetime','avoidable_defect'=>'boolean'];}

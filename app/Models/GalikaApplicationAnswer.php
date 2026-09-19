<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaApplicationAnswer extends Model { protected $table='galika_application_answers'; protected $guarded=[]; protected $casts=['humanized'=>'boolean','submitted_at'=>'datetime']; }

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaPlatformHealth extends Model { protected $table='galika_platform_health'; protected $guarded=[]; protected $casts=['last_checked'=>'datetime','last_success'=>'datetime','retry_after'=>'datetime']; }

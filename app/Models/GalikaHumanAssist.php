<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaHumanAssist extends Model{
 protected $guarded=[];
 protected $casts=['context'=>'array','resolved_at'=>'datetime','expires_at'=>'datetime','resumed_at'=>'datetime'];
}

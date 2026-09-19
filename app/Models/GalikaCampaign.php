<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaCampaign extends Model{protected $guarded=[];protected $casts=['plan'=>'array','next_action_at'=>'datetime'];}

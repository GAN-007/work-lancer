<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaWealthItem extends Model{
    protected $guarded=[];
    protected $casts=['next_action_at'=>'datetime','evidence'=>'array','execution_plan'=>'array'];
}

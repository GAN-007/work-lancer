<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaCanaryRun extends Model{
    protected $guarded=[];
    protected $casts=['steps'=>'array','evidence'=>'array','started_at'=>'datetime','finished_at'=>'datetime'];
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaScorecard extends Model
{
    protected $guarded=[];
    protected $casts=['period_start'=>'date','period_end'=>'date','evidence'=>'array'];
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaOutboxDeadLetter extends Model
{
    protected $guarded=[];
    protected $casts=['payload'=>'array','dead_lettered_at'=>'datetime','replayed_at'=>'datetime'];
}

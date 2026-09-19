<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaConversation extends Model{protected $guarded=[];protected $casts=['occurred_at'=>'datetime','metadata'=>'array'];}

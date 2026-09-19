<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaOffer extends Model{
 protected $guarded=[];
 protected $casts=['benefits'=>'array','analysis'=>'array','deadline_at'=>'datetime'];
 public function application(){return $this->belongsTo(GalikaApplication::class,'application_id');}
}

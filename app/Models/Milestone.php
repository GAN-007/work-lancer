<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Milestone extends Model{protected $guarded=[];protected $casts=['due_at'=>'datetime'];public function contract(){return $this->belongsTo(Contract::class);} }

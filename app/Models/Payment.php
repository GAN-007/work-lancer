<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Payment extends Model{protected $guarded=[];protected $casts=['proof'=>'array'];public function contract(){return $this->belongsTo(Contract::class);} }

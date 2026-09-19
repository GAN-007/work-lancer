<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class Contract extends Model{protected $guarded=[];public function milestones(){return $this->hasMany(Milestone::class);}public function payments(){return $this->hasMany(Payment::class);}public function job(){return $this->belongsTo(Job::class);} }

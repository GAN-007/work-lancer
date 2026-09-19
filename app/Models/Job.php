<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Job extends Model{
 use HasFactory;
 protected $fillable=['user_id','headline','title','job_id','category_id','description','skills','payment_category','pay_rate','status','task_deadline','freelancer_assigned_id'];
 protected $casts=['task_deadline'=>'datetime'];
 public function jobemployer(){return $this->belongsTo(User::class,'user_id');}
 public function contracts(){return $this->hasMany(Contract::class);}
}

<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class GalikaNextAction extends Model{protected $guarded=[];protected $casts=['reason'=>'array','due_at'=>'datetime','completed_at'=>'datetime'];}

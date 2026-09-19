<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaAnswerKnowledge extends Model{protected $guarded=[];protected $casts=['valid_until'=>'datetime','provenance'=>'array','material'=>'boolean'];}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaPolicy extends Model{protected $guarded=[];protected $casts=['rule'=>'array','enabled'=>'boolean'];}

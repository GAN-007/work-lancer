<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaWorkAuthorization extends Model{protected $guarded=[];protected $casts=['sponsorship_required'=>'boolean','remote_contractor_ok'=>'boolean','eor_ok'=>'boolean','willing_to_relocate'=>'boolean','evidence'=>'array'];}

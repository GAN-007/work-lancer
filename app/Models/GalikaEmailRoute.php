<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaEmailRoute extends Model{protected $guarded=[];protected $casts=['last_sent_at'=>'datetime','last_reconciled_at'=>'datetime','do_not_retry'=>'boolean'];}

<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GalikaRecruiterThread extends Model{
    protected $guarded=[];
    protected $casts=['participants'=>'array','last_message_at'=>'datetime','requires_human'=>'boolean'];
    public function application(){return $this->belongsTo(GalikaApplication::class,'application_id');}
}

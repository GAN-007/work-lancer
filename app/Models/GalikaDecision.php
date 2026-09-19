<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaDecision extends Model
{
    protected $table='galika_decisions';
    protected $guarded=[];
    protected $casts=['context'=>'array','resolved_at'=>'datetime'];

    public function application()
    {
        return $this->belongsTo(GalikaApplication::class,'application_id');
    }
}

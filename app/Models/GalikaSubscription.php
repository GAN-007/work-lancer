<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaSubscription extends Model
{
    protected $guarded=[];
    protected $casts=['started_at'=>'datetime','current_period_start'=>'datetime','current_period_end'=>'datetime','cancelled_at'=>'datetime','metadata'=>'array'];
    public function customer(){return $this->belongsTo(GalikaCustomer::class,'customer_id');}
}

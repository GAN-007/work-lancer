<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaCustomer extends Model
{
    protected $guarded=[];
    protected $casts=['won_at'=>'datetime','churned_at'=>'datetime','metadata'=>'array'];
    public function invoices(){return $this->hasMany(GalikaInvoice::class,'customer_id');}
    public function subscriptions(){return $this->hasMany(GalikaSubscription::class,'customer_id');}
}

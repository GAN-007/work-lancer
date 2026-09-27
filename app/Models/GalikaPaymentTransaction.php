<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaPaymentTransaction extends Model
{
    protected $guarded=[];
    protected $casts=['initiated_at'=>'datetime','confirmed_at'=>'datetime','failed_at'=>'datetime','request_payload'=>'array','provider_payload'=>'array'];
    public function invoice(){return $this->belongsTo(GalikaInvoice::class,'invoice_id');}
    public function customer(){return $this->belongsTo(GalikaCustomer::class,'customer_id');}
}

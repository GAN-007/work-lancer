<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalikaInvoice extends Model
{
    protected $guarded=[];
    protected $casts=['issued_at'=>'datetime','due_at'=>'datetime','paid_at'=>'datetime','lines'=>'array','metadata'=>'array'];
    public function customer(){return $this->belongsTo(GalikaCustomer::class,'customer_id');}
    public function payments(){return $this->hasMany(GalikaPaymentTransaction::class,'invoice_id');}
    public function wealthItem(){return $this->belongsTo(GalikaWealthItem::class,'wealth_item_id');}
}

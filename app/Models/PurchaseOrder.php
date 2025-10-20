<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id','order_number','order_date','expected_date','total','status','notes','created_by'
    ];

    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function items(){ return $this->hasMany(PurchaseOrderItem::class); }
    public function receipts(){ return $this->hasMany(PurchaseReceipt::class); }

    public function recalcTotal()
    {
        $this->load('items');
        $this->total = $this->items->sum('subtotal');
        $this->save();
    }
}

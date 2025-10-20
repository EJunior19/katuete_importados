<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id','product_id','quantity','unit_price','subtotal','status'];

    public function order(){ return $this->belongsTo(PurchaseOrder::class,'purchase_order_id'); }
    public function product(){ return $this->belongsTo(Product::class); }

    protected static function booted()
    {
        static::saving(function($item){
            $item->subtotal = ($item->quantity ?? 0) * ($item->unit_price ?? 0);
        });
        foreach (['saved','deleted'] as $evt) {
            static::{$evt}(function($item){ $item->order->recalcTotal(); });
        }
    }
}

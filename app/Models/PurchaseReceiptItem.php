<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptItem extends Model
{
    protected $fillable = [
        'purchase_receipt_id',
        'product_id',
        'ordered_qty',
        'received_qty',
        'unit_cost',
        'subtotal',
        'status',
        'reason',
        'comment',
    ];

    public function receipt(){ return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id'); }
    public function product(){ return $this->belongsTo(Product::class); }

    protected static function booted()
    {
        static::saving(function($i){
            $i->subtotal = ($i->received_qty ?? 0) * ($i->unit_cost ?? 0);
            // mantiene la lógica de estado
            $i->status = ($i->received_qty >= $i->ordered_qty)
                ? 'completo'
                : (($i->received_qty > 0) ? 'parcial' : 'faltante');
        });
    }
}

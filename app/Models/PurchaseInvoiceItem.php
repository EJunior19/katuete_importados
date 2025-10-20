<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    protected $fillable = [
        'purchase_invoice_id','purchase_receipt_item_id','product_id',
        'qty','unit_cost','tax_rate','subtotal','tax','total'
    ];

    public function invoice() { return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); }
    public function receiptItem(){ return $this->belongsTo(PurchaseReceiptItem::class, 'purchase_receipt_item_id'); }
    public function product(){ return $this->belongsTo(Product::class); }

    protected static function booted()
    {
        static::saving(function($it){
            $it->subtotal = ($it->qty ?? 0) * ($it->unit_cost ?? 0);
            $it->tax      = round($it->subtotal * (($it->tax_rate ?? 0)/100), 2);
            $it->total    = $it->subtotal + $it->tax;
        });
    }
}

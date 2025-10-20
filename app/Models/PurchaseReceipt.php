<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceipt extends Model
{
     protected $fillable = [
        'purchase_order_id',
        'receipt_number',
        'received_date',
        'received_by',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'received_date' => 'date',
        'approved_at'   => 'datetime',
    ];

    // Relaciones

    // Órdenes de compra e ítems recibidos
    public function order(){ return $this->belongsTo(PurchaseOrder::class,'purchase_order_id'); }
    public function items(){ return $this->hasMany(PurchaseReceiptItem::class); }

    // Usuarios
    public function receivedBy(){ return $this->belongsTo(\App\Models\User::class, 'received_by'); }
    public function approvedBy(){ return $this->belongsTo(\App\Models\User::class, 'approved_by'); }
}

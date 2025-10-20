<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public const TYPE_IN  = 'entrada';
    public const TYPE_OUT = 'salida';

    public const REF_SALE     = 'sale';
    public const REF_PURCHASE = 'purchase';
    public const REF_ADJUST   = 'adjust';

    protected $fillable = [
        'product_id',
        'type',       // 'entrada' | 'salida'
        'qty',        // entero/decimal
        'ref_type',   // 'sale','purchase','adjust',...
        'ref_id',
        'reason',     // ← columna real ahora
        'note',
        'user_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'qty'        => 'integer',
        'ref_id'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product() { return $this->belongsTo(Product::class); }
    public function user()    { return $this->belongsTo(User::class); }

    // (Opcional) fallback si reason viniera null:
    public function getReasonAttribute($value)
    {
        if (!empty($value)) return $value;

        return match ($this->ref_type) {
            self::REF_SALE     => 'Venta #'.$this->ref_id,
            self::REF_PURCHASE => 'Compra #'.$this->ref_id,
            self::REF_ADJUST   => 'Ajuste',
            default            => '—',
        };
    }
}

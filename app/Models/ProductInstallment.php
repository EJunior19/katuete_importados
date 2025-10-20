<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInstallment extends Model
{
    protected $fillable = [
        'product_id',
        'installments',
        'installment_price',
    ];


    // Para guaraníes suelo usar 0 decimales; cambiá a 'decimal:2' si querés ver centavos
    protected $casts = [
        'installments'      => 'integer',
        'installment_price' => 'integer',
    ];

    /** Relación inversa con Producto */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

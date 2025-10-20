<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',  // Si querés guardar la relación con productos
        'product_code',
        'product_name',
        'unit_price',
        'qty',
        'iva_type',
        'line_total',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }



    // Relación opcional con producto (si guardás product_id)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

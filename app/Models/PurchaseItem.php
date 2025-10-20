<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    // Si tu tabla NO tiene created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'qty',
        'cost',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    protected $casts = [
        'qty'  => 'integer',
        'cost' => 'integer',
    ];
}

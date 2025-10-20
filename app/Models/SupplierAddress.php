<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierAddress extends Model
{
    protected $fillable = [
        'supplier_id','street','city','state','country','postal_code','type','is_primary'
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
}

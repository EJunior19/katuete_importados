<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPhone extends Model
{
    protected $fillable = [
        'supplier_id','phone_number','type','is_active','is_primary'
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
}

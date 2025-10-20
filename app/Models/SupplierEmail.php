<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierEmail extends Model
{
    protected $fillable = ['supplier_id','email','type','is_default','is_active'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

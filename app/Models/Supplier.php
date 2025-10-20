<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','ruc','phone','email','address','active']; // 'code' lo genera la BD
    protected $casts = ['active' => 'boolean'];

    public function purchases()
    {
        return $this->hasMany(\App\Models\Purchase::class);
    }

    // Relaciones con teléfonos y direcciones
    public function addresses() { return $this->hasMany(SupplierAddress::class); }
    public function phones()    { return $this->hasMany(SupplierPhone::class); }

    public function primaryAddress() {
        return $this->hasOne(SupplierAddress::class)->where('is_primary', true);
    }
    // Relación con emails
    public function emails() { return $this->hasMany(SupplierEmail::class); }

    public function defaultPurchasingEmail() {
        return $this->emails()
            ->where('type','compras')
            ->where('is_default', true)
            ->first();
    }
    public function mainEmail()
    {
        return $this->hasOne(\App\Models\SupplierEmail::class)
            ->where(function ($q) {
                // si usás is_active, lo priorizamos; si no existe, no filtra
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderByRaw("(type = 'compras') DESC") // prioriza 'compras'
            ->orderByDesc('is_default')
            ->orderBy('id');
    }

    public function primaryPhone()
    {
        return $this->hasOne(\App\Models\SupplierPhone::class)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderByDesc('is_primary')
            ->orderBy('id');
    }
}

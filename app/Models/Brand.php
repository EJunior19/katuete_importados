<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    // Campos que se pueden asignar en masa (desde formularios)
    protected $fillable = ['name', 'active'];

    // Convertir automáticamente a boolean
    protected $casts = [
        'active' => 'boolean',
    ];

    // Relación: una marca tiene muchos productos
    public function products()
    {
        return $this->hasMany(\App\Models\Product::class);
    }
}

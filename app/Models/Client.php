<?php

// app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes; // quítalo si NO querés papelera

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'notes',
        'active',
        'ruc',
        'user_id',
        'telegram_chat_id', 
        'telegram_link_token',
        'telegram_linked_at',
        // 'code' lo pone el trigger; no hace falta en fillable
    ];

    protected $casts = [
        'active' => 'boolean',
        'telegram_chat_id' => 'integer',
        'telegram_linked_at' => 'datetime',
    ];

    // Ejemplo de scope rápido
    public function scopeActive($q) {
        return $q->where('active', 1);
    }
    // Relaciones
    public function user() {
        return $this->belongsTo(User::class);

    // (si luego relacionás ventas/contacts, ponelos aquí)
}
public function getDaysToDueAttribute()
{
    if (!$this->due_date) return null;
    return now()->startOfDay()->diffInDays($this->due_date, false); // +/-
}
// Helper: estado vinculado
public function getIsTelegramLinkedAttribute(): bool
{
    return !is_null($this->telegram_chat_id);
}

// Documentos relacionados
public function documents()
{
    return $this->hasMany(\App\Models\ClientDocument::class);
}

// Referencias relacionadas
public function references()
{
    return $this->hasMany(ClientReference::class);
}
// referencias que son a otros clientes
public function referencedClients()
{
    return $this->hasMany(\App\Models\ClientReference::class)
                ->whereNotNull('referenced_client_id')
                ->with('referencedClient');
}
}
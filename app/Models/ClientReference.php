<?php

// app/Models/ClientReference.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientReference extends Model
{
    protected $fillable = [
        'client_id','name','relationship','phone','email','address','note',
        'referenced_client_id',
        'telegram','telegram_chat_id','telegram_link_token',
        'notify_opt_in','notify_channels',
    ];

    protected $casts = [
        'notify_opt_in'   => 'boolean',
        'notify_channels' => 'array',
    ];

    public function client()            { return $this->belongsTo(Client::class); }
    public function referenced_client() { return $this->belongsTo(Client::class,'referenced_client_id'); }

    // canal preferente dinámico simple
    public function preferredChannels(): array
    {
        if ($this->notify_channels) return $this->notify_channels;
        // default: si hay chat_id => telegram, si no hay => whatsapp/email
        $channels = [];
        if ($this->telegram_chat_id) $channels[] = 'telegram';
        if ($this->phone)            $channels[] = 'whatsapp';
        if ($this->email)            $channels[] = 'email';
        return $channels ?: ['email'];
    }
}

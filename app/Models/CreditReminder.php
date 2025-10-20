<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditReminder extends Model
{
    protected $fillable = ['credit_id','due_date','days_before','sent_at'];

    protected $casts = [
        'due_date' => 'date',
        'sent_at'  => 'datetime',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}

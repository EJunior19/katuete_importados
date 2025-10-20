<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'number',
        'series',
        'issued_at',
        // 'display_number',   // ❌ NO hace falta (lo calculamos)
        'status',
        'subtotal',
        'tax',
        'total',
        'branch_code',
        'cash_register',
        'tax_stamp',
        'tax_stamp_valid_until',
    ];

    // ✅ 1) mantenemos append para exponer el atributo calculado
    protected $appends = ['display_number'];

    // ✅ 2) accessor seguro (soporta null)
    public function getDisplayNumberAttribute(): string
    {
        // si number ya viene completo, úsalo; si trae solo correlativo, compón con series
        $num = (string) ($this->number ?? '');
        if (preg_match('/^\d{3}-\d{3}-\d{7}$/', $num)) {
            return $num;
        }
        $serie = $this->series ?? '001-001';
        $seq   = str_pad($num, 7, '0', STR_PAD_LEFT);
        return "{$serie}-{$seq}";
    }

    protected $casts = [
        'issued_at' => 'date',
        'tax_stamp_valid_until' => 'date',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function client()
    {
        return $this->hasOneThrough(Client::class, Sale::class, 'id', 'id', 'sale_id', 'client_id');
    }

    /* =========================
     * Helpers
     * ========================= */

    public function getFormattedNumberAttribute(): string
    {
        $serie = $this->series ?? '001-001';
        $num = str_pad((string)($this->number ?? ''), 7, '0', STR_PAD_LEFT);
        return "{$serie}-{$num}";
    }

    public function getIssuedDateFormattedAttribute(): string
    {
        return $this->issued_at ? Carbon::parse($this->issued_at)->format('d/m/Y') : '—';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'issued'   => 'Emitida',
            'canceled' => 'Anulada',
            default    => ucfirst($this->status),
        };
    }

    public function calculateTax(): void
    {
        $this->tax = round(($this->subtotal ?? 0) * 0.1);
        $this->total = ($this->subtotal ?? 0) + ($this->tax ?? 0);
    }

    // ✅ 3) mutator: si asignás un número completo, separa series y correlativo
    public function setNumberAttribute($value): void
    {
        $val = (string) $value;
        if (preg_match('/^(\d{3}-\d{3})-(\d{7})$/', $val, $m)) {
            $this->attributes['series'] = $m[1];
            $this->attributes['number'] = (int) $m[2];
        } else {
            $this->attributes['number'] = is_numeric($val) ? (int)$val : null;
        }
    }
}

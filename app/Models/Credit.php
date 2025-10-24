<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Credit extends Model
{
    // ✅ Canónicos en ESPAÑOL (coinciden con el CHECK de Postgres)
    public const ST_PENDING = 'pendiente';
    public const ST_PARTIAL = 'parcial';
    public const ST_PAID    = 'pagado';

    protected $fillable = [
        'sale_id',
        'client_id',
        'amount',     // total de la cuota (Gs)
        'balance',    // saldo persistido (Gs)
        'due_date',
        'status',     // pendiente|parcial|pagado
    ];

    protected $casts = [
        'due_date'          => 'date',
        'amount'            => 'integer',
        'balance'           => 'integer',
        'last_notified_at'  => 'datetime',
        'next_notify_at'    => 'datetime',
        'notify_every_days' => 'integer',
        'auto_overdue'      => 'boolean',
    ];

    // ✅ Valor por defecto alineado con la base
    protected $attributes = [
        'status' => self::ST_PENDING,
    ];

    protected $appends = [
        'paid_amount',
        'computed_balance',
        'is_paid',
        'is_overdue',
    ];

    /* =========================
     * Normalización de status
     * ========================= */
    /** Permite setear en inglés o español y guarda SIEMPRE en español. */
    protected function setStatusAttribute($value): void
    {
        $v = mb_strtolower(trim((string)$value));

        $map = [
            // español
            'pendiente' => self::ST_PENDING,
            'parcial'   => self::ST_PARTIAL,
            'pagado'    => self::ST_PAID,
            // inglés -> español
            'pending'   => self::ST_PENDING,
            'partial'   => self::ST_PARTIAL,
            'paid'      => self::ST_PAID,
        ];

        $this->attributes['status'] = $map[$v] ?? self::ST_PENDING;
    }

    /** Ajusta el status según el balance (usa canónicos en español). */
    public function setStatusForBalance(): void
    {
        $this->status = $this->balance > 0 ? self::ST_PARTIAL : self::ST_PAID;
    }

    /* =========================
     * Relaciones
     * ========================= */
    public function client() { return $this->belongsTo(Client::class); }
    public function sale()   { return $this->belongsTo(Sale::class); }

    public function payments()
    {
        return $this->hasMany(Payment::class)
            ->orderBy('payment_date')
            ->orderBy('id');
    }

    /* =========================
     * Atributos calculados
     * ========================= */
    public function getPaidAmountAttribute(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->sum('amount');
        }
        return (int) $this->payments()->sum('amount');
    }

    public function getComputedBalanceAttribute(): int
    {
        return max(0, (int)$this->amount - (int)$this->paid_amount);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->computed_balance === 0;
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) return false;
        $due = $this->due_date instanceof Carbon ? $this->due_date : Carbon::parse($this->due_date);
        return !$this->is_paid && $due->isPast();
    }

    /* =========================
     * Helpers / Scopes
     * ========================= */
    /** Recalcula y persiste balance + status tomando los pagos actuales. */
    public function refreshAggregates(): void
    {
        $paid = (int) $this->payments()->sum('amount');
        $this->balance = max(0, (int)$this->amount - $paid);

        $this->status = $this->balance === 0
            ? self::ST_PAID
            : ($paid > 0 ? self::ST_PARTIAL : self::ST_PENDING);

        $this->save();
    }

    /** Devuelve cuotas no pagadas (cualquier cosa distinta de 'pagado'). */
    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', '!=', self::ST_PAID);
    }
}

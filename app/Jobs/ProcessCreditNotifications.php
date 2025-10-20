<?php

namespace App\Jobs;

use App\Models\Credit;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCreditNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tz     = config('app.timezone', 'UTC');
        $today  = Carbon::now($tz)->startOfDay();
        $token  = env('TELEGRAM_BOT_TOKEN');
        $enabled= (bool) env('TELEGRAM_BOT_ENABLED', true);

        // Buscar créditos con saldo pendiente
        $credits = Credit::query()
            ->with(['client:id,name,telegram_chat_id'])
            ->where('balance', '>', 0)
            ->whereIn('status', ['pendiente', 'vencido'])
            // evitar spam: sólo si next_notify_at es NULL o <= hoy
            ->where(function ($q) use ($today) {
                $q->whereNull('next_notify_at')
                  ->orWhereDate('next_notify_at', '<=', $today->toDateString());
            })
            ->orderBy('due_date')
            ->get();

        foreach ($credits as $credit) {
            $due = Carbon::parse($credit->due_date, $tz)->startOfDay();

            // Si ya venció y tiene saldo, marcar como 'vencido'
            if ($due->lt($today) && $credit->status !== 'vencido') {
                $credit->status = 'vencido';
            }

            // Enviar telegram sólo si hay chat_id y el bot está habilitado
            $messageSent = false;
            if ($enabled && $token && ($chat = $credit->client?->telegram_chat_id)) {
                $message = $this->buildMessage($credit, $today, $due);

                try {
                    Http::timeout(10)
                        ->asForm()
                        ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                            'chat_id'    => $chat,
                            'text'       => $message,
                            'parse_mode' => 'HTML',
                        ])
                        ->throw();

                    $messageSent = true;
                } catch (\Throwable $e) {
                    Log::warning('[CreditNotify] Telegram fallo', [
                        'credit_id' => $credit->id,
                        'client_id' => $credit->client_id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // Reprogramar próximo aviso (usa notify_every_days, default 7)
            $everyDays = (int)($credit->notify_every_days ?? 7);
            $credit->last_notified_at = $today->toDateString();
            $credit->next_notify_at   = $today->copy()->addDays(max(1, $everyDays))->toDateString();
            $credit->save();

            Log::info('[CreditNotify] Procesado crédito', [
                'credit_id' => $credit->id,
                'status'    => $credit->status,
                'sent'      => $messageSent,
                'next'      => $credit->next_notify_at,
            ]);
        }
    }

    private function fmtGs(float $n): string
    {
        return 'Gs. ' . number_format($n, 0, ',', '.');
    }

    private function buildMessage(Credit $c, Carbon $today, Carbon $due): string
    {
        $days = $today->diffInDays($due, false);
        if ($days < 0) {
            $vence = '⛔ <b>Vencido</b> hace ' . abs($days) . ' día' . (abs($days) === 1 ? '' : 's');
        } elseif ($days === 0) {
            $vence = '⚠️ <b>Vence hoy</b>';
        } else {
            $vence = '📅 Vence en ' . $days . ' día' . ($days === 1 ? '' : 's');
        }

        $monto  = $this->fmtGs((float)$c->amount);
        $saldo  = $this->fmtGs((float)$c->balance);
        $cliente= e($c->client?->name ?? 'Cliente');
        $idv    = $c->sale_id ? ('#' . $c->sale_id) : '—';

        return
            "Hola, <b>{$cliente}</b>\n" .
            "Crédito <b>#{$c->id}</b> (Venta: {$idv})\n\n" .
            "Monto: <b>{$monto}</b>\n" .
            "Saldo: <b>{$saldo}</b>\n" .
            "Vencimiento: <b>{$due->toDateString()}</b>\n" .
            "{$vence}\n\n" .
            "Si ya realizaste tu pago, ¡gracias! 🙌";
    }
}

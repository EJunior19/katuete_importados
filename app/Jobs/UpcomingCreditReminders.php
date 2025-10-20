<?php

namespace App\Jobs;

use App\Models\Credit;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class UpcomingCreditReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(TelegramService $tg): void
{
    $today = now()->startOfDay();
    $end   = now()->addDays(3)->endOfDay();

    Credit::with('client:id,name,telegram_chat_id')
        ->whereIn('status', ['pendiente','partial','pending'])
        ->whereNotNull('due_date')
        ->whereBetween('due_date', [$today, $end])
        ->whereHas('client', fn($q) => $q->whereNotNull('telegram_chat_id'))
        ->orderBy('id')
        ->chunkById(200, function ($credits) use ($tg, $today) {

            foreach ($credits as $cr) {
                $chatId = $cr->client?->telegram_chat_id;
                if (!$chatId) {
                    continue;
                }

                // Calcular offset (0 = hoy, 1 = mañana, 2, 3, etc.)
                $days = $today->diffInDays($cr->due_date->startOfDay(), false);
                if ($days < 0) {
                    // Ya vencido → lo maneja tu otro job de vencidos
                    continue;
                }
                if ($days > 3) {
                    // Fuera de ventana de este job
                    continue;
                }

                // Evitar reenvíos para el mismo crédito y mismo offset
                $tag = "{$days}d";
                $yaEnviado = DB::table('credit_events')
                    ->where('credit_id', $cr->id)
                    ->where('type', 'notified')
                    ->whereJsonContains('meta->auto', $tag) // meta = {"auto":"0d|1d|3d"}
                    ->exists();

                if ($yaEnviado) {
                    continue;
                }

                // Mensaje según el offset
                $cuando = match (true) {
                    $days === 0 => 'HOY',
                    $days === 1 => 'MAÑANA',
                    default     => "en {$days} días",
                };

                $fecha = optional($cr->due_date)->format('d/m/Y') ?? '—';
                $saldo = 'Gs. '.number_format((int)($cr->balance ?? 0), 0, ',', '.');

                $msg = "⏰ Aviso: tu cuota vence {$cuando} ({$fecha}) — Saldo: {$saldo} — Ref: #{$cr->id}";

                $ok = false;
                try {
                    $ok = $tg->sendMessage($chatId, $msg);
                } catch (\Throwable $e) {
                    // opcional: Log::warning('[Reminders] send exception', ['e'=>$e->getMessage()]);
                    $ok = false;
                }

                // Auditoría: registrar offset real
                DB::table('credit_events')->insert([
                    'credit_id'  => $cr->id,
                    'type'       => $ok ? 'notified' : 'error',
                    'meta'       => json_encode(['auto' => $tag]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Suavizar ritmo si querés
                usleep(50_000);
            }
        });
}
}   
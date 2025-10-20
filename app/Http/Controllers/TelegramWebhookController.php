<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1) Seguridad: validar secret header si está configurado
        $secret = (string) config('services.telegram.secret', '');
        if ($secret !== '') {
            $header = $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (!hash_equals($secret, $header)) {
                Log::warning('[TG] Webhook rechazado: secret inválido', ['got' => $header]);
                return response()->noContent(401);
            }
        }

        // 2) Cargar token
        $token = (string) config('services.telegram.token', '');
        if ($token === '') {
            Log::error('[TG] Falta services.telegram.token');
            return response()->noContent(500);
        }

        // 3) Tomar el "update" (message / edited_message / callback_query)
        $update   = $request->all();
        $message  = $update['message'] ?? $update['edited_message'] ?? null;
        $callback = $update['callback_query'] ?? null;

        if ($callback) {
            // Si más adelante usás botones inline, cae acá
            $chatId = data_get($callback, 'message.chat.id');
            $text   = (string) data_get($callback, 'data', '');
            Log::info('[TG] callback_query', ['data' => $text]);
            // Por ahora solo confirmamos recepción:
            $this->send($token, $chatId, '✅ Acción recibida.');
            return response()->noContent();
        }

        if (!$message) {
            // No es algo que nos interese procesar ahora
            return response()->noContent();
        }

        $chatId = data_get($message, 'chat.id');
        $text   = trim((string) data_get($message, 'text', ''));
        $name   = trim(((string) data_get($message, 'from.first_name', '')).' '.((string) data_get($message, 'from.last_name', '')));

        if ($text === '') {
            return response()->noContent();
        }

        // Comandos configurables
        $cmdLink   = config('telegram.commands.link', '/vincular');
        $cmdUnlink = config('telegram.commands.unlink', '/desvincular');
        $cmdSaldo  = config('telegram.commands.balance', '/saldo');

        // 4) /start → instrucciones o deep-link con token
        if (Str::startsWith(Str::lower($text), '/start')) {
            $parts = explode(' ', trim($text), 2);
            $tokenStart = $parts[1] ?? null;

            if ($tokenStart) {
                $client = Client::where('telegram_link_token', $tokenStart)->first();
                if ($client) {
                    // liberar chat_id si ya estaba en otro cliente
                    Client::where('telegram_chat_id', (string)$chatId)
                        ->where('id', '<>', $client->id)
                        ->update(['telegram_chat_id' => null]);

                    // vincular
                    $client->telegram_chat_id   = (int)$chatId;    // guardar como entero
                    $client->telegram_linked_at = now();
                    // opcional: invalidar token para que no se reutilice
                    // $client->telegram_link_token = null;
                    $client->save();

                    $this->reply($token, $message,
                        "✅ ¡Listo, <b>{$this->e($client->name)}</b>! Tu cuenta fue vinculada correctamente.");
                    return response()->noContent();
                }

                $this->reply($token, $message, "⚠️ El enlace ya no es válido o expiró.");
                return response()->noContent();
            }

            // Sin token → instrucciones
            $this->reply($token, $message, "👋 Hola <b>{$this->e($name)}</b>.\n\n".
                "Para vincular tu cuenta con RUC:\n".
                "<code>{$cmdLink} 80041278-0</code>\n\n".
                "Consultar saldo:\n<code>{$cmdSaldo}</code>\n".
                "Desvincular:\n<code>{$cmdUnlink}</code>");
            return response()->noContent();
        }

        // 5) /saldo → muestra saldo del cliente vinculado (si lo hay)
        if (Str::startsWith(Str::lower($text), Str::lower($cmdSaldo))) {
            $client = Client::where('telegram_chat_id', (string)$chatId)->first();
            if (!$client) {
                $this->reply($token, $message, "🔒 No hay una cuenta vinculada a este chat.\n".
                    "Vinculá tu RUC con:\n<code>{$cmdLink} &lt;RUC&gt;</code>");
                return response()->noContent();
            }

            // Buscar créditos pendientes del cliente (puedes ajustar la lógica)
            $credits = Credit::where('client_id', $client->id)
                ->orderBy('due_date')
                ->get(['id','amount','balance','due_date','status']);

            if ($credits->isEmpty()) {
                $this->reply($token, $message, "📄 No registramos créditos activos para <b>{$this->e($client->name)}</b>.");
                return response()->noContent();
            }

            $lineas = ["<b>Créditos de {$this->e($client->name)}</b>"];
            foreach ($credits as $c) {
                $lineas[] = sprintf(
                    "#%d • Monto: <b>%s</b> • Saldo: <b>%s</b> • Vence: <b>%s</b> • Estado: <b>%s</b>",
                    $c->id,
                    $this->fmt($c->amount),
                    $this->fmt($c->balance),
                    optional($c->due_date)->format('Y-m-d') ?? '—',
                    ucfirst($c->status)
                );
            }
            $this->reply($token, $message, implode("\n", $lineas));
            return response()->noContent();
        }

        // 6) /vincular <RUC>
            if (Str::startsWith(Str::lower($text), Str::lower($cmdLink))) {
                $parts = preg_split('/\s+/', $text);
                $rucIn = $parts[1] ?? '';

                if (!$rucIn) {
                    $this->reply($token, $message, "❗ Debes enviar tu RUC. Ejemplo:\n<code>{$cmdLink} 80041278-0</code>");
                    return response()->noContent();
                }

                // normalizar: quitar puntos/espacios, permitir con o sin guion
                $rucNorm = preg_replace('/[.\s]/', '', $rucIn);

                // valida básico (6 a 10 dígitos + dígito verificador opcional)
                if (!preg_match('/^\d{6,10}-?\d$/', $rucNorm)) {
                    $this->reply($token, $message, "⚠️ RUC inválido. Ej.: <code>80041278-0</code>");
                    return response()->noContent();
                }

                // buscar por ambas variantes (con y sin guión)
                $rucSinGuion = str_replace('-', '', $rucNorm);
                $rucConGuion = preg_replace('/^(\d+)(\d)$/', '$1-$2', $rucSinGuion);

                $client = Client::where('ruc', $rucNorm)
                    ->orWhere('ruc', $rucConGuion)
                    ->orWhereRaw("replace(ruc, '-', '') = ?", [$rucSinGuion])
                    ->first();

                if (!$client) {
                    $this->reply($token, $message, "😕 No encontramos un cliente con RUC <b>{$this->e($rucIn)}</b>.");
                    return response()->noContent();
                }

                // liberar chat_id en otros
                Client::where('telegram_chat_id', (string)$chatId)
                    ->where('id', '<>', $client->id)
                    ->update(['telegram_chat_id' => null]);

                // guardar chat_id + timestamp
                $client->telegram_chat_id   = (int)$chatId;
                $client->telegram_linked_at = now();
                $client->save();

                $this->reply($token, $message,
                    "✅ ¡Listo, <b>{$this->e($client->name)}</b>!\n".
                    "Tu cuenta quedó vinculada al RUC <b>{$this->e($client->ruc)}</b>. Recibirás recordatorios aquí. 💬");
                return response()->noContent();
            }

        // 7) /desvincular
        if (Str::startsWith(Str::lower($text), Str::lower($cmdUnlink))) {
            $client = Client::where('telegram_chat_id', (string)$chatId)->first();
            if (!$client) {
                $this->reply($token, $message, "No hay ninguna cuenta vinculada a este chat. 🙂");
                return response()->noContent();
            }

            $client->telegram_chat_id = null;
            $client->save();

            $this->reply($token, $message, "🔓 Vinculación eliminada. Podés volver a vincular con <code>{$cmdLink} &lt;RUC&gt;</code>.");
            return response()->noContent();
        }

        // 8) Fallback: ayuda
        $this->reply($token, $message,
            "ℹ️ Comandos disponibles:\n".
            "<code>/start</code>\n".
            "<code>{$cmdLink} &lt;RUC&gt;</code>\n".
            "<code>{$cmdSaldo}</code>\n".
            "<code>{$cmdUnlink}</code>"
        );

        return response()->noContent();
    }

    /* ---------- Helpers ---------- */

    private function send(string $token, string|int $chatId, string $text, ?int $replyTo = null): void
    {
        try {
            Http::timeout(10)->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'                  => (string) $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_to_message_id'      => $replyTo,
                    'allow_sending_without_reply' => true,
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('[TG] sendMessage falló', ['error' => $e->getMessage()]);
        }
    }

    private function reply(string $token, array $message, string $text): void
    {
        $chatId     = (string) data_get($message, 'chat.id');
        $messageId  = (int) data_get($message, 'message_id');
        $this->send($token, $chatId, $text, $messageId);
    }

    private function e(?string $s): string
    {
        return e($s ?? '');
    }

    private function fmt($n): string
    {
        return 'Gs. '.number_format((float)$n, 0, ',', '.');
    }
}

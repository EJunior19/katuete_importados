<?php
// app/Services/ContactOnboardingService.php
namespace App\Services;

use App\Models\ClientReference;
use Illuminate\Support\Str;

class ContactOnboardingService
{
    public function ensureDeepLink(ClientReference $ref): ?string
    {
        if ($ref->telegram_chat_id) {
            return null;
        }

        if (!$ref->telegram_link_token) {
            $ref->telegram_link_token = Str::random(24);
            $ref->save();
        }

        return $ref->telegram_link_token;
    }

    public function sendIntro(ClientReference $ref): void
    {
        $name  = $ref->name ?? optional($ref->referenced_client)->name ?? '¡Hola!';
        $intro = "Hola {$name}, te saluda *Katuete Importados* 👋\nTe comparto nuestro canal de atención y promos.";

        // 1) Telegram directo
        if ($ref->telegram_chat_id) {
            app(TelegramService::class)->sendMessage($ref->telegram_chat_id, $intro);
            return;
        }

        // 2) Usuario Telegram → enviar deep-link
        if ($ref->telegram) {
            $token = $this->ensureDeepLink($ref);
            $link  = "https://t.me/".config('bot.telegram.username')."?start={$token}";
            app(TelegramService::class)->notifyUsername($ref->telegram, "{$intro}\n\n👉 Ingresá acá: {$link}");
            return;
        }

        // 3) Fallback a WhatsApp/Email (si tenés gateway)
        // if ($ref->phone) { ... }
        // elseif ($ref->email) { ... }
    }
}

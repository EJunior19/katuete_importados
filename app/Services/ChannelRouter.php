<?php
// app/Services/ChannelRouter.php
namespace App\Services;

use App\Models\Client;

class ChannelRouter
{
    public function bestFor(Client $c): ?string
    {
        // 1) Telegram con chat_id guardado
        if (!empty($c->telegram_chat_id)) return 'telegram';

        // 2) WhatsApp/Phone (si hay teléfono)
        if (!empty($c->phone)) return 'whatsapp';

        // 3) Email
        if (!empty($c->email)) return 'email';

        return null;
    }

    public function destination(Client $c, string $channel): ?string
    {
        return match ($channel) {
            'telegram' => $c->telegram_chat_id,
            'whatsapp','sms' => preg_replace('/\D+/', '', (string) $c->phone),
            'email'    => $c->email,
            default    => null,
        };
    }
}

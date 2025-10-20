<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



class TelegramService
{
    public function sendMessage(int|string $chatId, string $text): bool
    {
        $token = config('services.telegram.token');
        $resp = Http::asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
        return $resp->ok();
    }
    public function getWebhookInfo(): ?array {
    try {
        $res = Http::timeout(10)->get("https://api.telegram.org/bot".config('services.telegram.token')."/getWebhookInfo");
        return $res->json('result') ?? null;
    } catch (\Throwable $e) {
        Log::warning('[TG] getWebhookInfo fail', ['e'=>$e->getMessage()]);
        return null;
    }
    }

    public function setWebhook(?string $url, ?string $secret = null): bool {
    try {
        $payload = ['url' => (string)$url, 'drop_pending_updates' => true];
        if ($secret) $payload['secret_token'] = $secret;
        $res = Http::asForm()->post("https://api.telegram.org/bot".config('services.telegram.token')."/setWebhook", $payload);
        return (bool)($res->json('ok') === true);
    } catch (\Throwable $e) {
        Log::warning('[TG] setWebhook fail', ['e'=>$e->getMessage()]);
        return false;
    }
    }
}

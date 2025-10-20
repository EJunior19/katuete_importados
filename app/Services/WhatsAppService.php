<?php
// app/Services/WhatsAppService.php
namespace App\Services;

class WhatsAppService
{
    /** No es proveedor oficial; devolvemos link wa.me para abrir */
    public function buildLink(string $phone, string $text): string
    {
        $n = preg_replace('/\D+/', '', $phone);
        return "https://wa.me/{$n}?text=".urlencode($text);
    }
}

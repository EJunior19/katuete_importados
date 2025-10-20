<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

Route::get('/telegram/webhook', function () {
    return response()->json([
        'ok' => true,
        'info' => 'Esta URL es solo diagnóstico. El webhook real es POST.'
    ]);
});

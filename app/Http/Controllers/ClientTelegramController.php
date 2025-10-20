<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientTelegramController extends Controller
{
    public function show(Client $client)
    {
        return view('clients.telegram', ['client' => $client]);
    }

    // Genera/renueva el token de deep-link
    public function generate(Request $request, Client $client)
    {
        $client->telegram_link_token = Str::random(40);
        $client->save();

        return back()->with('ok', 'Enlace mágico generado. Enviá el link al cliente.');
    }

    // Guarda manualmente el chat_id
    public function saveChatId(Request $request, Client $client)
    {
        $data = $request->validate([
            'telegram_chat_id' => [
                'required','numeric','min:1',
                Rule::unique('clients','telegram_chat_id')->ignore($client->id),
            ],
        ]);

        $client->telegram_chat_id   = (int) $data['telegram_chat_id'];
        $client->telegram_linked_at = now();
        $client->save();

        return back()->with('ok', 'chat_id guardado correctamente ✅');
    }

    // Envío de prueba
    public function ping(Request $request, Client $client)
    {
        if (!$client->telegram_chat_id) {
            return back()->with('err', 'Este cliente no tiene chat_id vinculado.');
        }

        $token = config('services.telegram.token');
        $text  = "👋 Hola, *{$client->name}*. Mensaje de prueba desde el ERP.";
        $res = Http::asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $client->telegram_chat_id,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);

        if (!$res->ok()) {
            Log::error('[TG] ping error', ['client_id'=>$client->id, 'res'=>$res->json()]);
            return back()->with('err', 'No se pudo enviar el mensaje (ver logs).');
        }

        return back()->with('ok', 'Mensaje enviado por Telegram ✅');
    }

    // Desvincular
    public function unlink(Request $request, Client $client)
    {
        $client->telegram_chat_id   = null;
        $client->telegram_linked_at = null;
        // opcional: revocar token
        // $client->telegram_link_token = null;
        $client->save();

        return back()->with('ok', 'Cliente desvinculado de Telegram.');
    }
}

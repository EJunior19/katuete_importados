<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TelegramLog;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;



class BotAdminController extends Controller
{
  public function index(TelegramService $tg)
  {
    $wh = $tg->getWebhookInfo(); // puede ser null si falla
    $linkedCount = Client::whereNotNull('telegram_chat_id')->count();
    $last24h = TelegramLog::where('created_at','>=',now()->subDay())->count();
    $last24hErrors = TelegramLog::where('created_at','>=',now()->subDay())->where('status','error')->count();

    $logs = TelegramLog::latest()->limit(20)->get(['id','client_id','direction','type','status','message','created_at']);
    $clients = Client::whereNotNull('telegram_chat_id')->latest('telegram_linked_at')->limit(12)->get(['id','name','telegram_chat_id','telegram_linked_at']);

    return view('bot.index', compact('wh','linkedCount','last24h','last24hErrors','logs','clients'));
  }

  public function setWebhook(Request $r, TelegramService $tg)
  {
    $url = $r->string('url')->toString();
    $secret = config('services.telegram.secret');
    abort_if(!$url, 400, 'Falta URL');

    $ok = $tg->setWebhook($url, $secret);
    return back()->with($ok ? 'ok' : 'err', $ok ? 'Webhook registrado' : 'No se pudo registrar el webhook');
  }

  public function testWebhook(Request $r)
{
    // Golpea tu webhook local para ver si responde 200
    $base   = rtrim(config('app.url'), '/');                 // ej: http://127.0.0.1:8000
    $url    = $base.'/api/telegram/webhook';
    $secret = (string) config('services.telegram.secret', '');

    $payload = [
        'message' => [
            'chat'        => ['id' => 9999],
            'message_id'  => 1,
            'text'        => '/start',
            'from'        => ['first_name' => 'Test'],
            'date'        => now()->timestamp,
        ],
    ];

    try {
        $res = Http::withHeaders([
                    'X-Telegram-Bot-Api-Secret-Token' => $secret,
                ])
                ->asJson() // explícito
                ->post($url, $payload);

        return back()->with(
            $res->successful() ? 'ok' : 'err',
            $res->successful()
                ? 'Webhook OK ('.$res->status().')'
                : 'Webhook respondió '.$res->status().' → '.$res->body()
        );
    } catch (\Throwable $e) {
        return back()->with('err', 'Error: '.$e->getMessage());
    }
}


  public function broadcastTest(TelegramService $tg)
  {
    $clients = Client::whereNotNull('telegram_chat_id')->pluck('telegram_chat_id','id');
    $sent=0; $err=0;
    foreach ($clients as $clientId=>$chat) {
      $ok = $tg->sendMessage($chat, "🔔 Prueba de difusión desde ERP Katuete (".now()->format('d/m H:i').")");
      TelegramLog::create([
        'client_id'=>$clientId,'direction'=>'out','type'=>'manual','status'=>$ok?'ok':'error',
        'message'=>'broadcast test','meta'=>json_encode(['chat_id'=>$chat]),
      ]);
      $ok ? $sent++ : $err++;
      usleep(40_000);
    }
    return back()->with('ok', "Difusión enviada. OK: {$sent}, Error: {$err}");
  }

  public function pingClient(Client $client, TelegramService $tg)
  {
    abort_unless($client->telegram_chat_id, 400, 'Cliente sin chat_id');
    $ok = $tg->sendMessage($client->telegram_chat_id, "✅ Ping desde ERP Katuete (".now()->format('d/m H:i').")");
    TelegramLog::create([
      'client_id'=>$client->id,'direction'=>'out','type'=>'manual','status'=>$ok?'ok':'error',
      'message'=>'ping','meta'=>json_encode(['chat_id'=>$client->telegram_chat_id]),
    ]);
    return back()->with($ok?'ok':'err', $ok?'Mensaje enviado':'No se pudo enviar');
  }

  public function regenerateLink(Client $client)
  {
    $client->telegram_link_token = Str::random(48);
    $client->save();

    return back()->with('ok','Link regenerado');
  }
}

<?php
// app/Http/Controllers/ContactDashboardController.php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactLog;
use App\Services\ChannelRouter;
use App\Jobs\SendContactMessage;
use Illuminate\Http\Request;

class ContactDashboardController extends Controller
{
    public function index(Request $request)
    {
        // KPIs rápidos
        $today = now()->startOfDay();
        $kpis = [
            'sent_today' => ContactLog::whereDate('sent_at', $today)->count(),
            'fails_today'=> ContactLog::whereDate('updated_at', $today)->where('status','fail')->count(),
            'queued'     => ContactLog::where('status','queued')->count(),
            'clients_no_channel' => \App\Models\Client::whereNull('telegram_chat_id')
                ->whereNull('email')->orWhereNull('phone')->count(),
        ];

        // Filtros básicos
        $q       = trim($request->get('q',''));
        $state   = $request->get('state'); // queued|sent|fail|null
        $channel = $request->get('channel'); // telegram|whatsapp|email|sms|null

        $logs = ContactLog::with('client')
            ->when($q, fn($qq)=>$qq->whereHas('client', fn($qc)=>
                $qc->where('name','ilike',"%{$q}%")->orWhere('ruc','ilike',"%{$q}%")))
            ->when($state, fn($qq)=>$qq->where('status',$state))
            ->when($channel, fn($qq)=>$qq->where('channel',$channel))
            ->latest()->paginate(15)->withQueryString();

        $clients = Client::orderBy('name')->limit(50)->get();

        return view('dashboard.contact.index', compact('kpis','logs','clients'));
    }

    public function send(Request $request, Client $client)
    {
        $data = $request->validate([
            'channel' => ['nullable','in:telegram,whatsapp,email,sms'],
            'type'    => ['nullable','string','max:32'],
            'message' => ['required','string','max:2000'],
        ]);

        $router = app(ChannelRouter::class);
        $channel = $data['channel'] ?? $router->bestFor($client);
        abort_if(!$channel, 422, 'El cliente no tiene un canal disponible.');

        $to = $router->destination($client, $channel);
        abort_if(!$to, 422, 'Destino inválido para el canal.');

        $log = ContactLog::create([
            'client_id' => $client->id,
            'channel'   => $channel,
            'type'      => $data['type'] ?? 'custom',
            'status'    => 'queued',
            'to_ref'    => (string)$to,
            'message'   => $data['message'],
        ]);

        SendContactMessage::dispatch($log);

        return back()->with('success', 'Mensaje encolado para envío.');
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'message'   => ['required','string','max:2000'],
            'channel'   => ['nullable','in:telegram,whatsapp,email,sms'],
            'client_ids'=> ['nullable','array'],
            'client_ids.*'=>['integer','exists:clients,id'],
        ]);

        $query = \App\Models\Client::query();
        if (!empty($data['client_ids'])) {
            $query->whereIn('id', $data['client_ids']);
        }
        $clients = $query->limit(500)->get();

        $router = app(ChannelRouter::class);

        foreach ($clients as $client) {
            $channel = $data['channel'] ?? $router->bestFor($client);
            if (!$channel) continue;
            $to = $router->destination($client, $channel);
            if (!$to) continue;

            $log = ContactLog::create([
                'client_id' => $client->id,
                'channel'   => $channel,
                'type'      => 'promo',
                'status'    => 'queued',
                'to_ref'    => (string)$to,
                'message'   => $data['message'],
            ]);
            SendContactMessage::dispatch($log);
        }

        return back()->with('success', 'Broadcast encolado.');
    }
}

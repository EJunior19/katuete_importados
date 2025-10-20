<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use App\Models\TelegramLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;

class DashboardController extends Controller
{
    public function index(TelegramService $tg)
    {
        // === Lo que ya tenías, tal cual ===
        $clientes           = Client::count();
        $clientesVinculados = Client::whereNotNull('telegram_chat_id')->count();

        $hoy             = now()->toDateString();
        $vencidosHoy     = Credit::whereIn('status', ['vencido','overdue'])
                                ->whereDate('due_date', $hoy)->count();
        $vencidosTotales = Credit::whereIn('status', ['vencido','overdue'])->count();
        $vencen3dias     = Credit::whereIn('status', ['pendiente','pending','partial'])
                                ->whereBetween('due_date', [now(), now()->addDays(3)])->count();

        $msg24h = TelegramLog::where('created_at', '>=', now()->subDay())->count();
        $err24h = TelegramLog::where('created_at', '>=', now()->subDay())
                             ->where('status', 'error')->count();

        $ultimosTG = TelegramLog::with('client:id,name')
                    ->latest()->limit(10)
                    ->get(['id','client_id','direction','type','status','message','created_at']);

        $ultimosCreditos = Credit::with('client:id,name')
                    ->latest('updated_at')->limit(10)
                    ->get(['id','client_id','status','balance','due_date','updated_at']);

        $wh = app(TelegramService::class)->getWebhookInfo();
        $webhookOnline = !empty($wh['url']);

        return view('dashboard', compact(
            'clientes','clientesVinculados',
            'vencidosHoy','vencidosTotales','vencen3dias',
            'msg24h','err24h','ultimosTG','ultimosCreditos','webhookOnline'
        ));
    }

    /**
     * Endpoint JSON que usa la UI (tarjetas, tablas, charts).
     * Acepta ?from=YYYY-MM-DD&to=YYYY-MM-DD (opcional).
     */
    public function stats(Request $req)
    {
        $from = $req->date('from') ?? now()->startOfMonth();
        $to   = $req->date('to')   ?? now();

        // --- KPIs base del ecosistema que ya tenés ---
        $kpis = [
            'clientes_total'      => (int) Client::count(),
            'clientes_vinculados' => (int) Client::whereNotNull('telegram_chat_id')->count(),
            'creditos_vencidos'   => (int) Credit::whereIn('status',['vencido','overdue'])->count(),
            'creditos_por_vencer' => (int) Credit::whereIn('status',['pendiente','pending','partial'])
                                        ->whereBetween('due_date', [now(), now()->addDays(7)])->count(),
            'telegram_24h'        => (int) TelegramLog::where('created_at','>=', now()->subDay())->count(),
            'telegram_err_24h'    => (int) TelegramLog::where('created_at','>=', now()->subDay())->where('status','error')->count(),
            // Suma de saldos (CxC) para tener un total global
            'cxc_saldo'           => (float) Credit::sum('balance'),
        ];

        // --- Serie: creditos que vencen por día (entre from/to) ---
        $vencimientos = Credit::selectRaw('DATE(due_date) as fecha, COUNT(*) as cantidad, SUM(balance) as saldo')
            ->whereBetween('due_date', [$from, $to])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // --- Serie: telegram por hora en últimas 24 hs (útil para actividad del bot) ---
        $desde24 = now()->subDay();
        $tg24 = TelegramLog::selectRaw("DATE_FORMAT(created_at, '%H:00') as hora, COUNT(*) as n")
            ->where('created_at', '>=', $desde24)
            ->groupBy('hora')->orderBy('hora')->get();

        // --- Top clientes por saldo ---
        $topClientes = Client::select('clients.id','clients.name','clients.ruc')
            ->leftJoin('credits','credits.client_id','=','clients.id')
            ->groupBy('clients.id','clients.name','clients.ruc')
            ->selectRaw('COALESCE(SUM(credits.balance),0) as saldo')
            ->orderByDesc('saldo')
            ->limit(10)
            ->get();

        // --- Estados de créditos (para donut) ---
        $estados = Credit::selectRaw('status, COUNT(*) as n, SUM(balance) as saldo')
            ->groupBy('status')
            ->get();

        // Respuesta
        return response()->json([
            'kpis'          => $kpis,
            'vencimientos'  => $vencimientos, // [{fecha, cantidad, saldo}]
            'telegram24h'   => $tg24,         // [{hora, n}]
            'topClientes'   => $topClientes,  // [{id,name,ruc,saldo}]
            'creditosEstado'=> $estados,      // [{status, n, saldo}]
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Credit;
use App\Services\TelegramService;

class CreditDashboardController extends Controller
{
    /**
     * Panel /dashboard/creditos
     */
    public function index(Request $r)
    {
        // 1) Subconsulta: última notificación por crédito
        $lastNotified = DB::table('credit_events')
            ->select('credit_id', DB::raw('MAX(created_at) as last_at'))
            ->where('type', 'notified')
            ->groupBy('credit_id');

        // 2) Query principal con left join a la subconsulta
        $q = Credit::query()
            ->leftJoinSub($lastNotified, 'ln', function ($join) {
                $join->on('ln.credit_id', '=', 'credits.id');
            })
            ->with(['client:id,name,ruc,telegram_chat_id'])
            ->select('credits.*', 'ln.last_at')
            ->when($r->filled('estado'), fn ($x) => $x->where('credits.status', $r->estado))
            ->when($r->filled('desde'), fn ($x) => $x->whereDate('credits.due_date', '>=', $r->desde))
            ->when($r->filled('hasta'), fn ($x) => $x->whereDate('credits.due_date', '<=', $r->hasta))
            ->when($r->filled('s'), function ($x) use ($r) {
                $s = "%{$r->s}%";
                $x->whereHas('client', fn ($c) =>
                    $c->where('name', 'like', $s)
                      ->orWhere('ruc', 'like', $s)
                );
            })
            // Urgencia primero
            // Urgencia primero (compatible con PostgreSQL)
            ->orderByRaw("
            CASE credits.status
                WHEN 'vencido' THEN 1
                WHEN 'partial' THEN 2
                WHEN 'pending' THEN 3
                WHEN 'paid'    THEN 4
                ELSE 5
            END
            ")
            ->orderByRaw('credits.due_date ASC NULLS LAST')

            ->orderBy('credits.due_date')
            ->paginate(15)
            ->appends($r->query());

        // 3) KPIs
        $hoyVencidos = Credit::where('status', 'vencido')
            ->whereDate('updated_at', now())->count();

        $prox3d = Credit::whereIn('status', ['pending', 'partial'])
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->count();

        $notificadosHoy = DB::table('credit_events')
            ->where('type', 'notified')
            ->whereDate('created_at', now())->count();

        $fallasHoy = DB::table('credit_events')
            ->where('type', 'error')
            ->whereDate('created_at', now())->count();

        $ultimoEvento = DB::table('credit_events')
            ->orderByDesc('created_at')
            ->value('created_at');

        // 4) Estado del webhook (badge Online/Offline)
        $webhookOk = cache()->remember('tg:webhook_ok', 60, function () {
            try {
                $token = config('services.telegram.token');
                if (!$token) return false;
                $json = @file_get_contents("https://api.telegram.org/bot{$token}/getWebhookInfo");
                if (!$json) return false;
                $info = json_decode($json, true);
                return ($info['ok'] ?? false) && !empty($info['result']['url']);
            } catch (\Throwable $e) {
                return false;
            }
        });

        return view('credits.dashboard', compact(
            'q',
            'hoyVencidos',
            'prox3d',
            'notificadosHoy',
            'fallasHoy',
            'ultimoEvento',
            'webhookOk'
        ));
    }

    /**
     * Acción del botón "Recordar" (envía mensaje por Telegram)
     */
    public function remind(Request $r, Credit $credit, TelegramService $tg)
    {
        $client = $credit->client;

        if (!$client?->telegram_chat_id) {
            return back()->with('err', 'Cliente no vinculado a Telegram');
        }

        $msg = "⏰ Recordatorio: tu cuota vence el " .
               optional($credit->due_date)->format('d/m/Y') .
               " por Gs. " . number_format((int) $credit->balance, 0, ',', '.') .
               ". Respondé si ya pagaste.";

        $ok = $tg->sendMessage($client->telegram_chat_id, $msg);

        DB::table('credit_events')->insert([
            'credit_id'  => $credit->id,
            'type'       => $ok ? 'notified' : 'error',
            'meta'       => json_encode(['manual' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with($ok ? 'ok' : 'err', $ok ? 'Recordatorio enviado' : 'Fallo al enviar');
    }
    public function stats()
{
    // KPIs básicas
    $total      = \App\Models\Credit::count();
    $vencidos   = \App\Models\Credit::where('status','vencido')->count();
    $pagados    = \App\Models\Credit::where('status','pagado')->count();
    $pendientes = \App\Models\Credit::whereIn('status',['pendiente','partial','pending'])->count();
    $moraGs     = \App\Models\Credit::where('status','vencido')->sum('balance');

    // 📊 Serie semanal de vencimientos (últimas 8 semanas) - PostgreSQL friendly
    $from = now()->subWeeks(7)->startOfWeek();
    $to   = now()->endOfWeek();

    $weekly = \App\Models\Credit::selectRaw("date_trunc('week', due_date) as wk, COUNT(*)::int as qty")
        ->whereBetween('due_date', [$from, $to])
        ->groupBy('wk')->orderBy('wk')->get();

    $wkLabels = $weekly->map(fn($r) => \Carbon\Carbon::parse($r->wk)->format('d/m')); // lunes de cada semana
    $wkData   = $weekly->pluck('qty');

    // 💸 Top 5 morosos (suma de balance por cliente)
    $top = \App\Models\Credit::with(['client:id,name,ruc'])
        ->selectRaw('client_id, SUM(balance)::bigint as total')
        ->where('balance','>',0)
        ->whereIn('status',['vencido','pendiente','partial','pending'])
        ->groupBy('client_id')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    return view('credits.stats', compact(
        'total','vencidos','pagados','pendientes','moraGs',
        'wkLabels','wkData','top'
    ));
            }

    public function logs(Request $r)
{
    $q = DB::table('credit_events as e')
        ->join('credits as c', 'c.id', '=', 'e.credit_id')
        ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
        ->select([
            'e.created_at','e.type','e.meta',
            'c.id as credit_id','c.status as credit_status',
            'cl.name as client_name','cl.ruc as client_ruc',
        ])
        // Filtros
        ->when($r->filled('type'), fn($x) => $x->where('e.type', $r->type))
        ->when($r->filled('desde'), fn($x) => $x->whereDate('e.created_at','>=',$r->desde))
        ->when($r->filled('hasta'), fn($x) => $x->whereDate('e.created_at','<=',$r->hasta))
        ->when($r->filled('s'), function($x) use($r){
            $s = "%{$r->s}%";
            $x->where(function($w) use($s){
                $w->where('cl.name','like',$s)
                  ->orWhere('cl.ruc','like',$s)
                  ->orWhere('c.id','like',$s);
            });
        })
        ->orderByDesc('e.created_at')
        ->paginate(25)
        ->appends($r->query());

    return view('credits.logs', compact('q'));
}


}

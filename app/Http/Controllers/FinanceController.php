<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class FinanceController extends Controller
{
    /**
     * Página principal del panel financiero
     */
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->endOfDay()->toDateString());

        return view('finance.index', compact('from', 'to'));
    }

    /**
     * Endpoint JSON con los datos del dashboard
     */
    public function stats(Request $request)
    {
        // Rango con límites del día (para incluir el día final completo)
        $from = Carbon::parse($request->query('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($request->query('to',   now()->endOfDay()))  ->endOfDay();

        // Cache 5 minutos por rango (YYYY-MM-DD HH:MM:SS)
        $key = 'finance:' . md5($from->toDateTimeString() . $to->toDateTimeString());

        $data = Cache::remember($key, 300, function () use ($from, $to) {

            // ===== VENTAS (usa 'estado' y 'fecha' si existe) =====
            $ventasQ = DB::table('sales')->whereIn('status', ['aprobado', 'approved', 'aprobada']);

            if (Schema::hasColumn('sales', 'fecha')) {
                // si tenés una columna fecha (Y-m-d), usamos ese rango
                $ventasQ->whereBetween('fecha', [$from->toDateString(), $to->toDateString()]);
            } else {
                $ventasQ->whereBetween('created_at', [$from, $to]);
            }

            $ventas_total = (int) $ventasQ->sum('total');

            // No hay columna de descuento en tu esquema actual
            $descuentos = 0;

            // ===== COMPRAS (tu purchase_items = qty * cost) =====
            $compras_total = 0;

            if (Schema::hasTable('purchase_items')) {
                $pi = DB::table('purchase_items as pi')
                    ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
                    ->whereIn('p.estado', ['aprobado', 'approved', 'aprobada']);

                // Rango por fecha de compra (prioriza purchased_at si existe)
                if (Schema::hasColumn('purchases', 'purchased_at')) {
                    $pi->whereBetween('p.purchased_at', [$from, $to]);
                } else {
                    $pi->whereBetween('p.created_at', [$from, $to]);
                }

                // Tu esquema tiene qty y cost
                $compras_total = (int) $pi->sum(DB::raw('COALESCE(pi.qty,0) * COALESCE(pi.cost,0)'));
            } else {
                // Si no hubiese ítems, intentar total en cabecera (si existiera)
                if (Schema::hasColumn('purchases', 'total')) {
                    $pq = DB::table('purchases as p')->whereIn('p.estado', ['aprobado', 'approved', 'aprobada']);
                    if (Schema::hasColumn('purchases', 'purchased_at')) {
                        $pq->whereBetween('p.purchased_at', [$from, $to]);
                    } else {
                        $pq->whereBetween('p.created_at', [$from, $to]);
                    }
                    $compras_total = (int) $pq->sum(DB::raw('COALESCE(p.total,0)'));
                } else {
                    $compras_total = 0;
                }
            }

            // ===== PAGOS (usa payment_date si existe; si no, created_at) =====
            $pagosQ = DB::table('payments');

            if (Schema::hasColumn('payments', 'payment_date')) {
                $pagosQ->whereBetween('payment_date', [$from, $to]);
            } else {
                $pagosQ->whereBetween('created_at', [$from, $to]);
            }

            $pagos_total = (int) $pagosQ->sum('amount');

            // ===== CxC (saldo pendiente) =====
            $cxc_saldo = (int) DB::table('credits')
                ->whereIn('status', ['pendiente', 'vencido', 'partial', 'pending'])
                ->sum('balance');

            // ===== FLUJO DIARIO DE COBROS =====
            $flujoQ = DB::table('payments');
            $dateExpr = Schema::hasColumn('payments', 'payment_date')
                ? 'DATE(payment_date)'
                : 'DATE(created_at)';

            if (Schema::hasColumn('payments', 'payment_date')) {
                $flujoQ->whereBetween('payment_date', [$from, $to]);
            } else {
                $flujoQ->whereBetween('created_at', [$from, $to]);
            }

            $flujo = $flujoQ
                ->selectRaw("$dateExpr as fecha, SUM(amount) as cobrado")
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get()
                ->map(fn($r) => [
                    'fecha'   => (string) $r->fecha,
                    'cobrado' => (int) $r->cobrado,
                ])
                ->values();

            // ===== TOP CLIENTES POR SALDO =====
            $topClientes = DB::table('credits as c')
                ->join('clients as cli', 'cli.id', '=', 'c.client_id')
                ->whereIn('c.status', ['pendiente', 'vencido', 'partial', 'pending'])
                ->selectRaw('cli.name, cli.ruc, SUM(c.balance) as saldo')
                ->groupBy('cli.name', 'cli.ruc')
                ->orderByDesc('saldo')
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    'name'  => $r->name,
                    'ruc'   => $r->ruc,
                    'saldo' => (int) $r->saldo,
                ])
                ->values();

            return [
                'ventas_total'   => $ventas_total,
                'descuentos'     => $descuentos,
                'compras_total'  => $compras_total,
                'pagos_total'    => $pagos_total,
                'cxc_saldo'      => $cxc_saldo,
                'flujo'          => $flujo,
                'topClientes'    => $topClientes,
            ];
        });

        return response()->json($data);
    }
}

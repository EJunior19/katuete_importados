<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Sale;
use App\Models\Client;
use App\Models\Product;
use App\Models\Credit;

class SaleController extends Controller
{
    /* ===============================
     * INDEX
     * =============================== */
    public function index(Request $request)
    {
        $q      = $request->q;
        $status = $request->status; // <- usar status (coincide con la DB)

        $sales = Sale::with('client')
            ->when($q, function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                        ->orWhere('nota', 'like', "%{$q}%");

                    // Si q es número, permite buscar por ID exacto
                    if (ctype_digit((string) $q)) {
                        $sub->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10);

        return view('sales.index', compact('sales'));
    }

    /* ===============================
     * CREATE
     * =============================== */
    public function create()
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);
        return view('sales.create', compact('clients'));
    }

    /* ===============================
     * STORE
     * =============================== */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'modo_pago' => 'required|in:contado,credito',
            'fecha'     => 'nullable|date',
            'primer_vencimiento' => 'nullable|date',
            'nota'      => 'nullable|string|max:2000',
            'items'     => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.iva_type'   => 'required|in:10,5,exento',
            'items.*.installments'      => 'nullable|integer|min:1',
            'items.*.installment_price' => 'nullable|numeric|min:0',
        ]);

        $isCredit = $data['modo_pago'] === 'credito';

        // Validación extra para crédito
        if ($isCredit) {
            foreach ($data['items'] as $i => $it) {
                if (empty($it['installments']) || empty($it['installment_price'])) {
                    return back()
                        ->withErrors(["items.$i.installments" => 'Elegí un plan de cuotas para cada ítem.'])
                        ->withInput();
                }
            }
        }

        // Cálculo de totales
        [$g10, $i10, $g5, $i5, $ex] = [0, 0, 0, 0, 0];
        foreach ($data['items'] as $it) {
            $line = (float) $it['unit_price'] * (int) $it['qty'];

            if ($it['iva_type'] === '10') {
                $grav = $line / 1.1;
                $g10 += (int) round($grav);
                $i10 += (int) round($line - $grav);
            } elseif ($it['iva_type'] === '5') {
                $grav = $line / 1.05;
                $g5  += (int) round($grav);
                $i5  += (int) round($line - $grav);
            } else {
                $ex  += (int) round($line);
            }
        }
        $totalIva = $i10 + $i5;
        $total    = $g10 + $g5 + $ex + $totalIva;

        return DB::transaction(function () use ($data, $isCredit, $g10, $i10, $g5, $i5, $ex, $totalIva, $total) {

            // Validar stock con bloqueo
            foreach ($data['items'] as $it) {
                $stock = (int) Product::whereKey($it['product_id'])
                    ->lockForUpdate()
                    ->value('stock');
                if ($stock < (int) $it['qty']) {
                    return back()
                        ->withErrors(['stock' => "Producto {$it['product_id']}: stock {$stock}, requerido {$it['qty']}"])
                        ->withInput();
                }
            }

            // Cabecera de venta
            $sale = Sale::create([
                'client_id'  => $data['client_id'],
                'modo_pago'  => $data['modo_pago'],
                'fecha'      => $data['fecha'],
                'nota'       => $data['nota'] ?? null,
                'status'     => 'pendiente_aprobacion', // <- status (ya no 'estado')
                'total'      => $total,
                'gravada_10' => $g10,
                'iva_10'     => $i10,
                'gravada_5'  => $g5,
                'iva_5'      => $i5,
                'exento'     => $ex,
                'total_iva'  => $totalIva,
            ]);

            // Detalle de ítems
            foreach ($data['items'] as $it) {
                $product = Product::find($it['product_id']);
                $sale->items()->create([
                    'product_id'   => $product?->id,
                    'product_code' => $product?->code,
                    'product_name' => $product?->name,
                    'unit_price'   => $it['unit_price'],
                    'qty'          => $it['qty'],
                    'iva_type'     => $it['iva_type'],
                    'line_total'   => $it['unit_price'] * $it['qty'],
                    'installments'      => $isCredit ? ($it['installments'] ?? null) : null,
                    'installment_price' => $isCredit ? ($it['installment_price'] ?? null) : null,
                ]);
            }

            // Generar cuotas si es crédito
            if ($isCredit) {
                $firstDue = $data['primer_vencimiento']
                    ? Carbon::parse($data['primer_vencimiento'])
                    : (!empty($data['fecha'])
                        ? Carbon::parse($data['fecha'])->addMonthNoOverflow()
                        : Carbon::now()->addMonthNoOverflow());

                foreach ($data['items'] as $it) {
                    $n   = (int) ($it['installments'] ?? 0);
                    $pc  = (float) ($it['installment_price'] ?? 0);
                    $qty = (int) $it['qty'];

                    if ($n > 0 && $pc > 0) {
                        for ($k = 1; $k <= $n; $k++) {
                            Credit::create([
                                'sale_id'   => $sale->id,
                                'client_id' => $data['client_id'],
                                'amount'    => (int) round($pc * $qty),
                                'balance'   => (int) round($pc * $qty),
                                'due_date'  => $firstDue->copy()->addMonthsNoOverflow($k - 1)->toDateString(),
                                'status' => 'pendiente_aprobacion',

                            ]);
                        }
                    }
                }
            }

            return redirect()->route('sales.show', $sale)
                ->with('ok', '✅ Venta registrada correctamente.');
        });
    }

    /* ===============================
     * SHOW
     * =============================== */
    public function show(Sale $sale)
    {
        $sale->load([
            'client',
            'items.product',
            'credits.payments',
            'invoice',
        ]);

        return view('sales.show', compact('sale'));
    }

    /* ===============================
     * EDIT
     * =============================== */
    public function edit(Sale $sale)
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);
        return view('sales.edit', compact('sale', 'clients'));
    }

    /* ===============================
     * UPDATE
     * =============================== */
    public function update(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'modo_pago' => 'required|in:contado,credito',
            'fecha'     => 'nullable|date',
            'primer_vencimiento' => 'nullable|date',
            'nota'      => 'nullable|string|max:2000',
            'status'    => 'required|in:pendiente_aprobacion,aprobado,rechazado,editable,cancelado',
            'items'     => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.iva_type'   => 'required|in:10,5,exento',
            'items.*.installments'      => 'nullable|integer|min:1',
            'items.*.installment_price' => 'nullable|numeric|min:0',
        ]);

        $isCredit = $data['modo_pago'] === 'credito';

        // Cálculo de totales
        [$g10, $i10, $g5, $i5, $ex] = [0, 0, 0, 0, 0];
        foreach ($data['items'] as $it) {
            $line = (float) $it['unit_price'] * (int) $it['qty'];

            if ($it['iva_type'] === '10') {
                $grav = $line / 1.1;
                $g10 += (int) round($grav);
                $i10 += (int) round($line - $grav);
            } elseif ($it['iva_type'] === '5') {
                $grav = $line / 1.05;
                $g5  += (int) round($grav);
                $i5  += (int) round($line - $grav);
            } else {
                $ex  += (int) round($line);
            }
        }
        $totalIva = $i10 + $i5;
        $total    = $g10 + $g5 + $ex + $totalIva;

        return DB::transaction(function () use ($sale, $data, $isCredit, $g10, $i10, $g5, $i5, $ex, $totalIva, $total) {

            // Actualizar ítems
            $sale->items()->delete();
            foreach ($data['items'] as $it) {
                $product = Product::find($it['product_id']);
                $sale->items()->create([
                    'product_id'   => $product?->id,
                    'product_code' => $product?->code,
                    'product_name' => $product?->name,
                    'unit_price'   => $it['unit_price'],
                    'qty'          => $it['qty'],
                    'iva_type'     => $it['iva_type'],
                    'line_total'   => $it['unit_price'] * $it['qty'],
                    'installments'      => $isCredit ? ($it['installments'] ?? null) : null,
                    'installment_price' => $isCredit ? ($it['installment_price'] ?? null) : null,
                ]);
            }

            // Validar stock si pasa a aprobado
            if ($data['status'] === 'aprobado') {
                foreach ($sale->items as $it) {
                    $stock = (int) Product::whereKey($it->product_id)
                        ->lockForUpdate()
                        ->value('stock');
                    if ($stock < (int) $it->qty) {
                        return back()
                            ->withErrors(['stock' => "Producto {$it->product_id}: stock {$stock}, requerido {$it->qty}"])
                            ->withInput();
                    }
                }
            }

            // Actualizar cabecera
            $sale->update([
                'client_id'  => $data['client_id'],
                'modo_pago'  => $data['modo_pago'],
                'fecha'      => $data['fecha'],
                'nota'       => $data['nota'] ?? null,
                'status'     => $data['status'], // <- status
                'total'      => $total,
                'gravada_10' => $g10,
                'iva_10'     => $i10,
                'gravada_5'  => $g5,
                'iva_5'      => $i5,
                'exento'     => $ex,
                'total_iva'  => $totalIva,
            ]);

            // Regenerar cuotas si es crédito
            $sale->credits()->delete();

            if ($isCredit) {
                $firstDue = !empty($data['primer_vencimiento'])
                    ? Carbon::parse($data['primer_vencimiento'])
                    : (!empty($data['fecha'])
                        ? Carbon::parse($data['fecha'])->addMonthNoOverflow()
                        : Carbon::now()->addMonthNoOverflow());

                foreach ($data['items'] as $it) {
                    $n   = (int) ($it['installments'] ?? 0);
                    $pc  = (float) ($it['installment_price'] ?? 0);
                    $qty = (int) $it['qty'];

                    if ($n > 0 && $pc > 0) {
                        for ($k = 1; $k <= $n; $k++) {
                            Credit::create([
                                'sale_id'   => $sale->id,
                                'client_id' => $data['client_id'],
                                'amount'    => (int) round($pc * $qty),
                                'balance'   => (int) round($pc * $qty),
                                'due_date'  => $firstDue->copy()->addMonthsNoOverflow($k - 1)->toDateString(),
                                'status'    => Credit::ST_PENDING,
                            ]);
                        }
                    }
                }
            }

            return redirect()->route('sales.show', $sale)
                ->with('ok', '✅ Venta actualizada correctamente.');
        });
    }

    /* ===============================
     * PRINT
     * =============================== */
    public function print(Sale $sale)
    {
        if ($sale->status !== 'aprobado') { // <- status
            return redirect()->route('sales.show', $sale)
                ->with('error', '⚠️ Solo se pueden imprimir ventas aprobadas.');
        }

        $sale->load('client', 'items.product');
        return view('sales.print', compact('sale'));
    }

    /* ===============================
     * UPDATE STATUS
     * =============================== */
    public function updateStatus(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'status' => 'required|in:pendiente_aprobacion,aprobado,rechazado,editable,cancelado',
        ]);

        if ($data['status'] === 'aprobado') {
            foreach ($sale->items as $it) {
                $stock = (int) Product::whereKey($it->product_id)
                    ->lockForUpdate()
                    ->value('stock');
                if ($stock < (int) $it->qty) {
                    return back()
                        ->withErrors(['stock' => "Producto {$it->product_id}: stock {$stock}, requerido {$it->qty}"])
                        ->withInput();
                }
            }
        }

        $sale->update(['status' => $data['status']]); // <- status

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Estado actualizado correctamente.');
    }
}

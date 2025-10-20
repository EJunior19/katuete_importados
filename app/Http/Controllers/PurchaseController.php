<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // para reglas avanzadas
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;



class PurchaseController extends Controller
{
    // ================== INDEX ==================
    public function index()
    {
        $purchases = Purchase::query()
            ->select('purchases.*')
            ->selectSub(
                DB::table('purchase_items')
                    ->selectRaw('COALESCE(SUM(qty * cost), 0)')
                    ->whereColumn('purchase_items.purchase_id', 'purchases.id'),
                'total_amount'
            )
            ->with('supplier')
            ->latest()
            ->paginate(12);

        return view('purchases.index', compact('purchases'));
    }

    // ================== CREATE ==================
    public function create()
    {
        // Código decorativo (no se guarda en BD)
        $nextId = null;
        if (DB::getDriverName() === 'pgsql') {
            $seqRow = DB::selectOne("SELECT pg_get_serial_sequence('purchases','id') AS seq");
            if ($seqRow && $seqRow->seq) {
                $nextId = DB::selectOne("SELECT last_value + 1 AS next_id FROM {$seqRow->seq}")->next_id ?? null;
            }
        } else {
            $nextId = DB::table('information_schema.TABLES')
                ->where('TABLE_SCHEMA', env('DB_DATABASE'))
                ->where('TABLE_NAME', 'purchases')
                ->value('AUTO_INCREMENT');
        }
        $codePreview = $nextId ? sprintf('PUR-%05d', $nextId) : null;

        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        return view('purchases.create', [
            'nextId'    => $nextId,
            'code'      => $codePreview,
            'suppliers' => $suppliers,
            'products'  => $products,
        ]);
    }

    // ================== STORE ==================
    public function store(Request $request)
    {
        // Alias para el vencimiento del timbrado (acepta ambos nombres)
        $request->merge([
            'timbrado_expiration' => $request->input('timbrado_expiration', $request->input('vencimiento_timbrado')),
        ]);

        // Validación
        $validated = $request->validate([
            'supplier_id'          => ['required','exists:suppliers,id'],
            'purchased_at'         => ['required','date'],
            'notes'                => ['nullable','string'],
            'estado'               => ['nullable','in:pendiente,aprobado,rechazado'],

            'invoice_number'       => ['required','string','max:30'],
            'timbrado'             => ['required','string','max:20'],
            'timbrado_expiration'  => ['required','date','after_or_equal:purchased_at'],

            'items'                 => ['required','array','min:1'],
            'items.*.product_id'    => ['required','exists:products,id'],
            'items.*.qty'           => ['required','numeric','min:1'],
            'items.*.cost'          => ['required','numeric','min:0'],
        ]);

        // Unicidad factura+timbrado por proveedor
        $request->validate([
            'invoice_number' => [
                Rule::unique('purchases')->where(function ($q) use ($request) {
                    return $q->where('supplier_id', $request->supplier_id)
                            ->where('timbrado', $request->timbrado)
                            ->where('invoice_number', $request->invoice_number);
                })
            ],
        ]);

        $purchase = null;

        DB::transaction(function () use ($validated, &$purchase) {
            // Cabecera
            $purchase = Purchase::create([
                'supplier_id'         => $validated['supplier_id'],
                'purchased_at'        => $validated['purchased_at'],
                'notes'               => $validated['notes'] ?? null,
                'estado'              => $validated['estado'] ?? 'pendiente',
                'invoice_number'      => $validated['invoice_number'],
                'timbrado'            => $validated['timbrado'],
                'timbrado_expiration' => $validated['timbrado_expiration'],
                'created_by'          => Auth::id(),
                'updated_by'          => Auth::id(),
            ]);

            // items
        $total = 0;

        foreach ($validated['items'] as $it) {
            $subtotal = (float)$it['qty'] * (float)$it['cost'];

            DB::table('purchase_items')->insert([
                'purchase_id' => $purchase->id,
                'product_id'  => (int)$it['product_id'],
                'qty'         => (float)$it['qty'],
                'cost'        => (float)$it['cost'],
                // 'subtotal'  => $subtotal,  // ❌ QUITAR
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $total += $subtotal;
        }

        // Si tu tabla purchases tiene columna total, actualízala
        if (Schema::hasColumn('purchases', 'total')) {
            $purchase->update(['total' => $total]);
        }


                    // Importante: NO mover stock acá si se aprueba vía trigger al cambiar estado a 'aprobado'
                });

                // 👇 SIEMPRE devolver algo
                return redirect()
                    ->route('purchases.show', $purchase->id)
                    ->with('success', 'Compra guardada correctamente.');
            }


// ================== APPROVE (solo estado) ==================
public function approve(Purchase $purchase)
{
    // Seguridad básica: evitar reaprobar
    abort_if($purchase->estado === 'aprobado', 400, 'Esta compra ya está aprobada.');

    // Validar que existan datos fiscales (factura y timbrado)
    if (! $purchase->invoice_number || ! $purchase->timbrado || ! $purchase->timbrado_expiration) {
        return back()->withErrors('Para aprobar una compra se requiere N° de factura, timbrado y vencimiento.');
    }

    // ✅ Actualizar estado y usuario que aprobó
    $purchase->update([
        'estado'     => 'aprobado',
        'updated_by' => Auth::id(), // se usa en el trigger
    ]);

    return back()->with('ok', 'Compra aprobada correctamente.');
}


    // ================== SHOW ==================
    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product');
        return view('purchases.show', compact('purchase'));
    }

    // ================== EDIT ==================
    public function edit(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        $suppliers = Supplier::orderBy('name')->get();
        $total = $purchase->items->sum(fn ($it) => (int)($it->qty ?? 0) * (int)($it->cost ?? 0));

        return view('purchases.edit', compact('purchase', 'suppliers', 'total'));
    }

    // ================== UPDATE (solo cabecera) ==================
    public function update(Request $request, Purchase $purchase)
    {
        // Soportar ambos nombres para el vencimiento del timbrado
        $request->merge([
            'timbrado_expiration' => $request->input('timbrado_expiration', $request->input('vencimiento_timbrado')),
        ]);

        $validated = $request->validate([
            'supplier_id'          => ['required', 'exists:suppliers,id'],
            'purchased_at'         => ['required', 'date'],
            'notes'                => ['nullable', 'string'],
            'estado'               => ['nullable', 'in:pendiente,aprobado,rechazado'],

            // NUEVOS CAMPOS CABECERA
            'invoice_number'       => ['nullable','string','max:255'],
            'timbrado'             => ['nullable','string','max:20'],
            'timbrado_expiration'  => ['nullable','date'],
        ]);

        DB::transaction(function () use ($purchase, $validated, $request) {
            $purchase->update([
                'supplier_id'          => $validated['supplier_id'],
                'purchased_at'         => $validated['purchased_at'],
                'notes'                => $validated['notes'] ?? null,
                'estado'               => $validated['estado'] ?? $purchase->estado,
                'invoice_number'       => $validated['invoice_number'] ?? null,
                'timbrado'             => $validated['timbrado'] ?? null,
                'timbrado_expiration'  => $validated['timbrado_expiration'] ?? null,
            ]);

            // Si más adelante editás ítems en este form, acá iría la lógica de items.
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Compra actualizada correctamente.');
    }

    // ================== UPDATE SOLO ESTADO ==================
    public function updateStatus(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'estado' => ['required', 'in:pendiente,aprobado,rechazado'],
        ]);

        $purchase->update(['estado' => $validated['estado']]);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', "Estado de la compra #{$purchase->id} actualizado a {$validated['estado']}.");
    }

    // ================== DESTROY ==================
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()
            ->route('purchases.index')
            ->with('success', 'Compra eliminada');
    }
}

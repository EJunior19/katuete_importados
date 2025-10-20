<?php

namespace App\Http\Controllers;

use App\Models\{
    PurchaseOrder,
    PurchaseOrderItem,
    Supplier,
    Product,
    PurchaseReceipt
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    /**
     * Listado con filtros básicos.
     * GET /purchase_orders
     */
    public function index(Request $request)
    {
        $q = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('items')
            ->latest('id');

        // Filtros
        if ($request->filled('supplier_id')) {
            $q->where('supplier_id', $request->integer('supplier_id'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }
        if ($request->filled('from')) {
            $q->whereDate('order_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('order_date', '<=', $request->date('to'));
        }
        if ($request->filled('search')) {
            $s = trim($request->get('search'));
            $q->where(function($qq) use ($s) {
                $qq->where('order_number', 'ilike', "%{$s}%")
                   ->orWhereHas('supplier', fn($w) => $w->where('name', 'ilike', "%{$s}%"));
            });
        }

        $orders = $q->paginate(15)->withQueryString();

        // Para filtros en la vista
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase_orders.index', compact('orders', 'suppliers'));
    }

    /**
     * Formulario de creación.
     * GET /purchase_orders/create
     */
    public function create(Request $request)
    {
        return view('purchase_orders.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products'  => Product::orderBy('name')->get(),
            // permite precargar proveedor via ?supplier_id=
            'prefill'   => [
                'supplier_id' => $request->integer('supplier_id') ?: null,
                'order_date'  => now()->toDateString(),
            ],
        ]);
    }

    /**
     * Persistencia de OC + ítems.
     * POST /purchase_orders
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'   => ['required','exists:suppliers,id'],
            'order_date'    => ['required','date'],
            'expected_date' => ['nullable','date','after_or_equal:order_date'],
            'notes'         => ['nullable','string','max:2000'],

            'items'                     => ['required','array','min:1'],
            'items.*.product_id'       => ['required','exists:products,id'],
            'items.*.quantity'         => ['required','integer','min:1'],
            'items.*.unit_price'       => ['nullable','numeric','min:0'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id'   => $data['supplier_id'],
                'order_number'  => $this->generateOrderNumber(),
                'order_date'    => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => auth()->id(),
                'status'        => 'borrador',
            ]);

            foreach ($data['items'] as $row) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'  => $row['product_id'],
                    'quantity'    => (int) $row['quantity'],
                    'unit_price'  => (float) ($row['unit_price'] ?? 0),
                ]);
            }

            // Recalcular total (usa tu método del modelo)
            $order->recalcTotal();

            return $order;
        });

        return redirect()
            ->route('purchase_orders.show', $order)
            ->with('success','OC creada correctamente');
    }

    /**
     * Detalle.
     * GET /purchase_orders/{purchase_order}
     */
    public function show(PurchaseOrder $purchase_order)
{
    $purchase_order->load([
        'supplier.addresses' => fn($q) => $q->orderByDesc('is_primary'),
        'supplier.phones'    => fn($q) => $q->orderByDesc('is_primary'),
        'supplier.emails'    => fn($q) => $q->orderByDesc('is_default'),
        'items.product',
        'receipts.items',
    ]);

    // Suma recibida (solo recepciones APROBADAS) agrupada por producto para ESTA OC:
    $receivedByProduct = DB::table('purchase_receipt_items as pri')
        ->join('purchase_receipts as pr', 'pr.id', '=', 'pri.purchase_receipt_id')
        ->where('pr.purchase_order_id', $purchase_order->id)
        ->where('pr.status', 'aprobado')
        ->select('pri.product_id', DB::raw('SUM(pri.received_qty)::int as received_qty'))
        ->groupBy('pri.product_id')
        ->pluck('received_qty', 'product_id');   // [product_id => qty]


    return view('purchase_orders.show', [
        'purchase_order'     => $purchase_order,
        'receivedByProduct'  => $receivedByProduct,
    ]);
}

    /**
     * Formulario de edición.
     * GET /purchase_orders/{purchase_order}/edit
     */
    public function edit(PurchaseOrder $purchase_order)
    {
        $purchase_order->load(['items.product','supplier']);
        return view('purchase_orders.edit', [
            'order'     => $purchase_order,
            'suppliers' => Supplier::orderBy('name')->get(),
            'products'  => Product::orderBy('name')->get(),
        ]);
    }

    /**
     * Actualización de cabecera + sincronización de ítems.
     * PUT /purchase_orders/{purchase_order}
     *
     * Admite items con estructura:
     *  - si viene id => actualiza el ítem
     *  - si no viene id => crea nuevo ítem
     *  - si un ítem existente NO viene en el array => se elimina
     */
    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $data = $request->validate([
            'supplier_id'   => ['required','exists:suppliers,id'],
            'order_date'    => ['required','date'],
            'expected_date' => ['nullable','date','after_or_equal:order_date'],
            'notes'         => ['nullable','string','max:2000'],
            'status'        => ['nullable', Rule::in(['borrador','enviado','recibido','cerrado'])],

            'items'                     => ['required','array','min:1'],
            'items.*.id'               => ['nullable','integer','min:1'],
            'items.*.product_id'       => ['required','exists:products,id'],
            'items.*.quantity'         => ['required','integer','min:1'],
            'items.*.unit_price'       => ['nullable','numeric','min:0'],
        ]);

        DB::transaction(function () use ($data, $purchase_order) {
            // Proteger contra ediciones si ya está 'cerrado'
            if ($purchase_order->status === 'cerrado') {
                abort(422, 'La OC está cerrada y no se puede editar.');
            }

            $purchase_order->update([
                'supplier_id'   => $data['supplier_id'],
                'order_date'    => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'status'        => $data['status'] ?? $purchase_order->status,
            ]);

            // Mapear ítems entrantes por id (si existe)
            $incoming = collect($data['items']);

            // 1) Actualizar/crear
            $keepIds = [];
            foreach ($incoming as $row) {
                $payload = [
                    'product_id' => (int) $row['product_id'],
                    'quantity'   => (int) $row['quantity'],
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                ];

                if (!empty($row['id'])) {
                    $item = PurchaseOrderItem::where('purchase_order_id', $purchase_order->id)
                        ->where('id', (int)$row['id'])
                        ->first();

                    if ($item) {
                        $item->update($payload);
                        $keepIds[] = $item->id;
                    }
                } else {
                    $item = PurchaseOrderItem::create([
                        'purchase_order_id' => $purchase_order->id,
                    ] + $payload);
                    $keepIds[] = $item->id;
                }
            }

            // 2) Borrar los que no vinieron (sólo si hay keepIds)
            if (count($keepIds) > 0) {
                PurchaseOrderItem::where('purchase_order_id', $purchase_order->id)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }

            // 3) Recalcular total
            $purchase_order->recalcTotal();
        });

        return redirect()
            ->route('purchase_orders.show', $purchase_order)
            ->with('success','OC actualizada correctamente');
    }

    /**
     * Eliminar OC (protege si hay recepciones).
     * DELETE /purchase_orders/{purchase_order}
     */
    public function destroy(PurchaseOrder $purchase_order)
    {
        // Bloquear si hay recepciones (cascada/consistencia)
        $hasReceipts = PurchaseReceipt::where('purchase_order_id', $purchase_order->id)->exists();
        if ($hasReceipts) {
            return back()->with('error','No puedes eliminar la OC porque tiene recepciones registradas.');
        }

        DB::transaction(function () use ($purchase_order) {
            // borra ítems y luego la OC (tienes cascade, pero lo hacemos explícito)
            $purchase_order->items()->delete();
            $purchase_order->delete();
        });

        return redirect()->route('purchase_orders.index')->with('success','OC eliminada');
    }

    /**
     * Opcional: cambiar estado a 'enviado'
     */
    public function send(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'borrador') {
            return back()->with('error','Solo las OC en borrador pueden enviarse.');
        }
        $purchase_order->update(['status' => 'enviado']);
        return back()->with('success','OC marcada como enviada.');
    }

    /**
     * Opcional: cerrar OC manualmente
     */
    public function close(PurchaseOrder $purchase_order)
    {
        if (!in_array($purchase_order->status, ['enviado','recibido'])) {
            return back()->with('error','La OC debe estar enviada o recibida para cerrarse.');
        }
        $purchase_order->update(['status' => 'cerrado']);
        return back()->with('success','OC cerrada.');
    }

    /**
     * Opcional: reabrir si estaba cerrada (sin recepciones abiertas)
     */
    public function reopen(PurchaseOrder $purchase_order)
    {
        if ($purchase_order->status !== 'cerrado') {
            return back()->with('error','Solo una OC cerrada puede reabrirse.');
        }
        $hasPendingReceipts = $purchase_order->receipts()
            ->whereIn('status', ['borrador','pendiente_aprobacion'])
            ->exists();
        if ($hasPendingReceipts) {
            return back()->with('error','No se puede reabrir porque hay recepciones pendientes.');
        }
        $purchase_order->update(['status' => 'enviado']);
        return back()->with('success','OC reabierta (estado: enviado).');
    }

    /**
     * Generador simple de número de OC.
     * Si quieres algo más robusto (con secuencia por año), lo movemos a la BD.
     */
    protected function generateOrderNumber(): string
    {
        return 'OC-'.now()->format('YmdHis');
    }
}

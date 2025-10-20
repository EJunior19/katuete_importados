<?php

namespace App\Http\Controllers;

use App\Models\{
    PurchaseInvoice,
    PurchaseInvoiceItem,
    PurchaseReceipt,
    PurchaseReceiptItem,
    Product
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = PurchaseInvoice::query()
            ->with(['receipt.order.supplier'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchase_invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $receiptId = $request->integer('receipt');
        $selected  = null;

        if ($receiptId) {
            // Trae la recepción + OC + proveedor + productos + sumatoria ya facturada
            $selected = PurchaseReceipt::with([
                'order.supplier',
                'items' => function ($q) {
                    $q->with(['product:id,name,code'])
                      ->withSum('invoiceItems as invoiced_qty', 'qty')
                      ->orderBy('id');
                },
            ])->findOrFail($receiptId);

            // Agrega remaining_qty (recibido - ya facturado)
            $selected->items->transform(function ($it) {
                $invoiced = (int) ($it->invoiced_qty ?? 0);
                $it->remaining_qty = max(0, (int)$it->received_qty - $invoiced);
                return $it;
            });
        }

        // últimas 50 recepciones para el selector
        $receipts = PurchaseReceipt::with('order.supplier')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('purchase_invoices.create', [
            'receipts' => $receipts,
            'selected' => $selected,
        ]);
    }

    public function store(Request $request)
    {
        // Validación base
        $data = $request->validate([
            'purchase_receipt_id'                  => ['required','exists:purchase_receipts,id'],
            'invoice_number'                       => ['required','string','max:50'],
            'invoice_date'                         => ['required','date'],
            'notes'                                => ['nullable','string','max:2000'],

            'items'                                 => ['required','array','min:1'],
            'items.*.purchase_receipt_item_id'      => ['required','exists:purchase_receipt_items,id'],
            'items.*.product_id'                    => ['required','exists:products,id'],
            'items.*.qty'                           => ['required','integer','min:1'],
            'items.*.unit_cost'                     => ['required','numeric','min:0'],
            'items.*.tax_rate'                      => ['nullable','numeric','min:0','max:100'],
        ]);

        // Traer la recepción y los ítems con sumatoria ya facturada
        $receipt = PurchaseReceipt::with([
            'order.supplier',
            'items' => function ($q) {
                $q->withSum('invoiceItems as invoiced_qty', 'qty');
            }
        ])->findOrFail($data['purchase_receipt_id']);

        // Construir mapa de "remaining" por ítem de recepción
        $remainingByReceiptItem = [];
        foreach ($receipt->items as $rit) {
            $already = (int) ($rit->invoiced_qty ?? 0);
            $remainingByReceiptItem[$rit->id] = max(0, (int)$rit->received_qty - $already);
        }

        // Validación de negocio: no facturar más de lo disponible
        $errors = [];
        foreach ($data['items'] as $idx => $row) {
            $rid = (int) $row['purchase_receipt_item_id'];
            $qty = (int) $row['qty'];

            $remaining = $remainingByReceiptItem[$rid] ?? null;
            if ($remaining === null) {
                $errors["items.$idx.purchase_receipt_item_id"] = 'El ítem de recepción no pertenece a la recepción indicada.';
                continue;
            }
            if ($qty > $remaining) {
                $errors["items.$idx.qty"] = "La cantidad ($qty) excede la disponible para facturar ($remaining).";
            }
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors($errors);
        }

        try {
            DB::transaction(function () use ($data, $receipt, &$invoice) {
                // Crear cabecera de factura
                $invoice = PurchaseInvoice::create([
                    'purchase_receipt_id' => $receipt->id,
                    'invoice_number'      => $data['invoice_number'],
                    'invoice_date'        => $data['invoice_date'],
                    'notes'               => $data['notes'] ?? null,
                    'created_by'          => auth()->id(),
                    'status'              => 'emitida', // o 'borrador' si prefieres
                    'subtotal'            => 0,
                    'tax'                 => 0,
                    'total'               => 0,
                ]);

                $sumSubtotal = 0.0;
                $sumTax      = 0.0;
                $sumTotal    = 0.0;

                foreach ($data['items'] as $row) {
                    $qty       = (int) $row['qty'];
                    $unitCost  = (float) $row['unit_cost'];
                    $taxRate   = (float) ($row['tax_rate'] ?? 0);

                    $lineSubtotal = round($qty * $unitCost, 2);
                    $lineTax      = round($lineSubtotal * ($taxRate / 100), 2);
                    $lineTotal    = round($lineSubtotal + $lineTax, 2);

                    PurchaseInvoiceItem::create([
                        'purchase_invoice_id'       => $invoice->id,
                        'purchase_receipt_item_id'  => (int) $row['purchase_receipt_item_id'],
                        'product_id'                => (int) $row['product_id'],
                        'qty'                       => $qty,
                        'unit_cost'                 => $unitCost,
                        'tax_rate'                  => $taxRate,
                        'subtotal'                  => $lineSubtotal,
                        'tax'                       => $lineTax,
                        'total'                     => $lineTotal,
                    ]);

                    $sumSubtotal += $lineSubtotal;
                    $sumTax      += $lineTax;
                    $sumTotal    += $lineTotal;
                }

                // Actualiza totales de la factura
                $invoice->update([
                    'subtotal' => $sumSubtotal,
                    'tax'      => $sumTax,
                    'total'    => $sumTotal,
                ]);
            });
        } catch (QueryException $e) {
            // 23505 = unique_violation (p.ej. invoice_number duplicado por receipt)
            if ((string) $e->getCode() === '23505') {
                return back()
                    ->withInput()
                    ->withErrors(['invoice_number' => 'Ya existe una factura con ese número para esta recepción.']);
            }
            throw $e;
        }

        return redirect()
            ->route('purchase_invoices.show', $invoice)
            ->with('success', 'Factura registrada');
    }

    public function show(PurchaseInvoice $purchase_invoice)
    {
        $purchase_invoice->load([
            'receipt.order.supplier',
            'items.product',
            'items.receiptItem', // relación: PurchaseInvoiceItem -> purchase_receipt_item
        ]);

        return view('purchase_invoices.show', compact('purchase_invoice'));
    }
}

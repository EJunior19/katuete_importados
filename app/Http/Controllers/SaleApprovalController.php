<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleApprovalController extends Controller
{
    public function approve(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'issued_at'      => ['nullable','date'],
            'series'         => ['nullable','string','max:15'],
            'invoice_number' => ['nullable','string','max:30'],
        ]);

        DB::transaction(function () use (&$sale, $data) {
            // 1) lock
            $locked = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            // 2) aprobar si corresponde (idempotente)
            if ($locked->status !== 'aprobado') {
                if ($locked->status !== 'pendiente_aprobacion') {
                    throw new \RuntimeException('No se puede aprobar: la venta no está pendiente de aprobación.');
                }

                $updated = Sale::query()
                    ->whereKey($locked->id)
                    ->where('status', 'pendiente_aprobacion')
                    ->update([
                        'status'      => 'aprobado',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        'updated_at'  => now(),
                    ]);

                if ($updated === 0) {
                    throw new \RuntimeException('No se pudo aprobar: cambió de estado.');
                }
                $sale = Sale::query()->lockForUpdate()->findOrFail($locked->id);
            } else {
                $sale = $locked;
            }

            // 3) crear factura si no existe
            if (!$sale->invoice) {
                $series = $data['series'] ?? '001-001';

                // totales desde la venta
                $subtotal = (int)($sale->gravada_10 ?? 0) + (int)($sale->gravada_5 ?? 0) + (int)($sale->exento ?? 0);
                $tax      = (int)($sale->total_iva ?? 0);
                $total    = (int)($sale->total ?? 0);

                // Si el usuario dio un número completo, lo usamos
                if (!empty($data['invoice_number'])) {
                    $display = trim($data['invoice_number']);

                    $invoice = $sale->invoice()->create([
                        // gracias al mutator, 'number' acepta "001-001-0000123" o correlativo
                        'number'      => $display,
                        'series'      => $series, // si vino 001-001-0000123, el mutator lo sobreescribe
                        'issued_at'   => $data['issued_at'] ?? now()->toDateString(),
                        'status'      => 'issued',
                        'subtotal'    => $subtotal,
                        'tax'         => $tax,
                        'total'       => $total,
                        'branch_code' => null,
                        'cash_register' => null,
                        'tax_stamp'   => null,
                        'tax_stamp_valid_until' => null,
                    ]);
                } else {
                    // autogenerar correlativo por serie
                    $last = Invoice::query()
                        ->where('series', $series)
                        ->whereNotNull('number')
                        ->max('number');

                    $next = (int)$last + 1;

                    $invoice = $sale->invoice()->create([
                        'series'      => $series,
                        'number'      => $next,
                        'issued_at'   => $data['issued_at'] ?? now()->toDateString(),
                        'status'      => 'issued',
                        'subtotal'    => $subtotal,
                        'tax'         => $tax,
                        'total'       => $total,
                        'branch_code' => null,
                        'cash_register' => null,
                        'tax_stamp'   => null,
                        'tax_stamp_valid_until' => null,
                    ]);
                }
            }
        });

        return redirect()
            ->route('sales.show', $sale->id)
            ->with('success', 'Venta aprobada y factura generada.');
    }

    public function reject(Sale $sale)
    {
        DB::transaction(function () use (&$sale) {
            $locked = Sale::query()->whereKey($sale->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'aprobado') {
                throw new \RuntimeException('No se puede rechazar: la venta ya fue aprobada.');
            }

            $updated = Sale::query()
                ->whereKey($locked->id)
                ->where('status', '!=', 'aprobado')
                ->update([
                    'status'      => 'rechazado',
                    'approved_by' => null,
                    'approved_at' => null,
                    'updated_at'  => now(),
                ]);

            if ($updated === 0) {
                throw new \RuntimeException('No se pudo rechazar.');
            }

            $sale = Sale::findOrFail($locked->id);
        });

        return redirect()
            ->route('sales.show', $sale->id)
            ->with('success', 'Venta rechazada.');
    }
}

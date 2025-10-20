<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Credit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Registrar un pago (abono) a un crédito.
     *
     * Soporta dos modos:
     *  - Ruta anidada:  POST /credits/{credit}/payments  (name: credits.payments.store)
     *      Firma: store(Request $request, ?Credit $credit)
     *  - Ruta plana:    POST /payments                   (name: payments.store)
     *      Firma: store(Request $request) con hidden credit_id
     */
    public function store(Request $request, ?Credit $credit = null)
    {
        // 1) Resolver el crédito según el tipo de ruta
        if (!$credit) {
            // Ruta plana: exige credit_id
            $request->validate([
                'credit_id' => ['required', 'exists:credits,id'],
            ]);
            $credit = Credit::findOrFail($request->input('credit_id'));
        }

        // 2) Normalizar monto (acepta "1.500.000" -> 1500000)
        $amountClean = (int) preg_replace('/\D/', '', (string) $request->input('amount'));

        // 3) Validar campos (con amount ya normalizado)
        $request->merge(['amount' => $amountClean]);
        $request->validate([
            'amount'       => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'method'       => ['nullable', 'string', 'max:100'],
            'reference'    => ['nullable', 'string', 'max:100'],
            'note'         => ['nullable', 'string', 'max:500'],
        ]);

        // 4) Regla de negocio: el abono no puede superar el saldo
        if ($amountClean > (int) $credit->balance) {
            return back()
                ->withErrors(['amount' => 'El abono no puede superar el saldo pendiente.'])
                ->withInput();
        }

        // 5) Persistir en transacción
        DB::transaction(function () use ($credit, $amountClean, $request) {
            // Crear pago
            Payment::create([
                'credit_id'    => $credit->id,
                'amount'       => $amountClean,           // entero en Gs.
                'payment_date' => $request->payment_date, // yyyy-mm-dd
                'method'       => $request->method,       // opcional
                'reference'    => $request->reference,    // opcional
                'note'         => $request->note,         // opcional
            ]);

            // Actualizar saldo y estado
            $credit->balance = max(0, (int) $credit->balance - $amountClean);
            if ($credit->balance === 0) {
                $credit->status = 'pagado';
            }
            $credit->save();

            // Recalcular agregados si existe el método
            if (method_exists($credit, 'refreshAggregates')) {
                $credit->refreshAggregates();
            }
        });

        return redirect()
            ->route('credits.show', $credit)
            ->with('ok', '✅ Pago registrado correctamente.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReceipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class PurchaseApprovalController extends Controller
{
    /**
     * Aprobar recepción:
     * - Solo si no está "rechazado".
     * - Idempotente: si ya está "aprobado", no hace nada.
     * - Stock/OC lo maneja la BD vía triggers.
     */
    public function approve(PurchaseReceipt $receipt)
    {
        try {
            DB::transaction(function () use (&$receipt) {
                // Bloqueo para evitar carreras
                $locked = PurchaseReceipt::query()
                    ->whereKey($receipt->getKey())
                    ->lockForUpdate()
                    ->with(['items', 'order'])
                    ->firstOrFail();

                if ($locked->status === 'aprobado') {
                    $receipt = $locked;
                    return; // idempotente
                }

                if ($locked->status === 'rechazado') {
                    throw new \RuntimeException('No se puede aprobar: la recepción fue rechazada.');
                }

                $locked->update([
                    'status'      => 'aprobado',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                $receipt = $locked;
            });
        } catch (QueryException $e) {
            // Triggers (RAISE EXCEPTION) en PostgreSQL -> SQLSTATE P0001
            if ($e->getCode() === 'P0001') {
                return back()->with('error', $e->getMessage());
            }
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchase_receipts.show', $receipt->id)
            ->with('success', 'Recepción aprobada.');
    }

    /**
     * Rechazar recepción:
     * - No permite rechazar si ya está "aprobado".
     * - Limpia sello de aprobación si existía.
     */
    public function reject(PurchaseReceipt $receipt)
    {
        try {
            DB::transaction(function () use (&$receipt) {
                $locked = PurchaseReceipt::query()
                    ->whereKey($receipt->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status === 'aprobado') {
                    throw new \RuntimeException('No se puede rechazar: la recepción ya fue aprobada.');
                }

                if ($locked->status !== 'rechazado') {
                    $locked->update([
                        'status'      => 'rechazado',
                        'approved_by' => null,
                        'approved_at' => null,
                    ]);
                }

                $receipt = $locked;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === 'P0001') {
                return back()->with('error', $e->getMessage());
            }
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchase_receipts.show', $receipt->id)
            ->with('success', 'Recepción rechazada.');
    }
}

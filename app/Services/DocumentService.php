<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Genera y guarda todos los PDFs necesarios.
     * Usa tus vistas si ya existen; sino, apunta a las mínimas.
     */
    public function generateAll(Sale $sale, Invoice $invoice): void
    {
        // FACTURA (siempre)
        $this->renderAndStore(
            // Usa tu plantilla existente si querés:
            // 'reports.exports.sales-pdf',
            'pdf.invoice',
            ['sale' => $sale, 'invoice' => $invoice],
            "invoices/{$invoice->number}.pdf"
        );

        // RECIBO si es contado
        if (($sale->modo_pago ?? $sale->payment_mode ?? 'contado') === 'contado') {
            $this->renderAndStore(
                // 'reports.exports.receipt-pdf', // si tenés
                'pdf.receipt',
                ['sale' => $sale, 'invoice' => $invoice],
                "receipts/{$invoice->number}.pdf"
            );
        }

        // CONTRATO + CRONOGRAMA si es crédito
        if (($sale->modo_pago ?? $sale->payment_mode ?? 'contado') === 'credito') {
            $sale->loadMissing('credits'); // asegurar cronograma
            $this->renderAndStore(
                // 'reports.exports.credit-contract-pdf',
                'pdf.credit_contract',
                ['sale' => $sale, 'invoice' => $invoice],
                "contracts/credit-{$invoice->number}.pdf"
            );

            $this->renderAndStore(
                // 'reports.exports.installment-schedule-pdf',
                'pdf.installment_schedule',
                ['sale' => $sale, 'invoice' => $invoice, 'credits' => $sale->credits],
                "schedules/{$invoice->number}.pdf"
            );
        }
    }

    protected function renderAndStore(string $view, array $data, string $path): void
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4');
        Storage::disk('public')->put($path, $pdf->output());
    }
}

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #111; }
    .center { text-align: center; }
  </style>
</head>
<body>
  <h2 class="center">💵 RECIBO DE PAGO</h2>

  <p><strong>Cliente:</strong> {{ $sale->client->name ?? '—' }}</p>
  <p><strong>Factura N°:</strong> {{ $invoice->number }}</p>
  <p><strong>Fecha:</strong> {{ now()->format('d/m/Y') }}</p>
  <p><strong>Monto recibido:</strong> Gs. {{ number_format($invoice->total, 0, ',', '.') }}</p>

  <p style="margin-top: 40px;">Firma del cliente: ____________________________</p>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; color: #111; }
    h2 { text-align: center; }
  </style>
</head>
<body>
  <h2>📄 CONTRATO DE CRÉDITO</h2>

  <p>En la ciudad de ___________________, a los {{ now()->format('d') }} días del mes de {{ now()->translatedFormat('F') }} del año {{ now()->format('Y') }}, entre <strong>Katuete Importados</strong> y el Sr./Sra. <strong>{{ $sale->client->name ?? '—' }}</strong>, se celebra el presente contrato de crédito por la suma de <strong>Gs. {{ number_format($invoice->total, 0, ',', '.') }}</strong>.</p>

  <p>El cliente se compromete a abonar el monto total en las cuotas pactadas, conforme al cronograma de pagos adjunto, bajo las condiciones de interés y vencimiento acordadas.</p>

  <p>Firma del cliente: _________________________</p>
  <p>Firma de Katuete Importados: _________________________</p>
</body>
</html>


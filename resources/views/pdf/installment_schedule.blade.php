<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h2 { text-align: center; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #777; padding: 5px; text-align: center; }
    th { background: #e8e8e8; }
  </style>
</head>
<body>
  <h2>📆 Cronograma de Cuotas</h2>
  <p><strong>Cliente:</strong> {{ $sale->client->name ?? '—' }}</p>
  <p><strong>Factura N°:</strong> {{ $invoice->number }}</p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha de vencimiento</th>
        <th>Monto</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      @foreach($credits ?? [] as $i => $c)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ \Carbon\Carbon::parse($c->due_date)->format('d/m/Y') }}</td>
          <td>Gs. {{ number_format($c->amount, 0, ',', '.') }}</td>
          <td>{{ ucfirst($c->status ?? 'pendiente') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p style="margin-top: 40px;">Firma del cliente: ____________________________</p>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; }
    h1 { text-align: center; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #999; padding: 5px; }
    th { background-color: #e8e8e8; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h1>Factura N° {{ $invoice->number }}</h1>

  <p><strong>Cliente:</strong> {{ $sale->client->name ?? '—' }}</p>
  <p><strong>Fecha de emisión:</strong> {{ $invoice->issued_at->format('d/m/Y') }}</p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Producto</th>
        <th class="right">Cant.</th>
        <th class="right">Precio</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->items as $i => $item)
      <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $item->product_name ?? $item->product->name ?? '—' }}</td>
        <td class="right">{{ $item->qty }}</td>
        <td class="right">Gs. {{ number_format($item->unit_price,0,',','.') }}</td>
        <td class="right">Gs. {{ number_format($item->line_total,0,',','.') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <p class="right"><strong>Total:</strong> Gs. {{ number_format($invoice->total, 0, ',', '.') }}</p>
  <p class="right"><strong>IVA:</strong> Gs. {{ number_format($invoice->tax, 0, ',', '.') }}</p>

  <p style="margin-top: 40px;">__________________________________<br>Firma del cliente</p>
</body>
</html>

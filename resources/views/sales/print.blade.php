<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ticket Venta #{{ $sale->id }}</title>
  <style>
    body { font-family: monospace, sans-serif; font-size: 12px; }
    .center { text-align: center; }
    .line { border-top: 1px dashed #000; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 2px; }
    th { text-align: left; }
    .right { text-align: right; }
  </style>
</head>
<body onload="window.print()">

  <div class="center">
    <h3>Katuete Importados</h3>
    <p>Venta N° {{ $sale->id }}<br>
       Fecha: {{ $sale->fecha?->format('d/m/Y H:i') ?? $sale->created_at->format('d/m/Y H:i') }}</p>
  </div>

  <div class="line"></div>
  <table>
    <thead>
      <tr>
        <th>Prod</th>
        <th>Cant</th>
        <th class="right">Precio</th>
      </tr>
    </thead>
    <tbody>
      @foreach($sale->items as $it)
        <tr>
          <td>{{ \Illuminate\Support\Str::limit($it->product_name,12) }}</td>
          <td>{{ $it->qty }}</td>
          <td class="right">{{ number_format($it->line_total,0,',','.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="line"></div>
  <p class="right">TOTAL: Gs. {{ number_format($sale->total,0,',','.') }}</p>
  <div class="line"></div>

  <div class="center">
    ¡Gracias por su compra!<br>
    Katuete Importados
  </div>

</body>
</html>

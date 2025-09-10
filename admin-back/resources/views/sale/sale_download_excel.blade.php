<table>
  <thead>
    <tr>
      <th width="15">N°</th>
      <th width="25">Cliente</th>
      <th width="25">Tipo CLiente</th>
      <th width="25">Asesor</th>
      <th width="25">Sucursal</th>
      <th width="25">Descuento</th>
      <th width="25">Subtotal</th>
      <th width="25">IVA</th>
      <th width="25">Total</th>
      <th width="25">Deuda</th>
      <th width="25">Pagado</th>
      <th width="25">Tipo</th>
      <th width="25">Estado de pago</th>
      <th width="25">Estado de entrega</th>
      <th width="25">Fecha de registro</th>
    </tr>
  </thead>

  <tbody>|
    @foreach ($sales as $sale)
      <tr>
        <td>{{ $sale->id }}</td>
        <td>{{ $sale->client->name }}</td>
        <td>{{ $sale->type_client == 1 ? 'Cliente' : 'Cliente Empresa' }}</td>
        <td>{{ $sale->user->name }}</td>
        <td>{{ $sale->sucursale->name }}</td>
        <td>$ {{ $sale->discount }}</td>
        <td>$ {{ $sale->subtotal }}</td>
        <td>$ {{ $sale->iva }}</td>
        <td>$ {{ $sale->total }}</td>
        <td>$ {{ $sale->debt }}</td>
        <td>$ {{ $sale->paid_out }}</td>
        <td>{{ $sale->state == 1 ? 'Venta' : 'Cotización' }}</td>
        @if($sale->state_mayment == 1)
          <td style="background: #ffeb9c;">PENDIENTE</td> 
        @endif
        @if($sale->state_mayment == 2)
          <td style="background: #F7FFBF;">PARCIAL</td> 
        @endif
        @if($sale->state_mayment == 3)
          <td style="background: #bbfccb;">PAGADO</td> 
        @endif

        @if($sale->state_delivery == 1)
          <td style="background: #ffeb9c;">PENDIENTE</td> 
        @endif
        @if($sale->state_delivery == 2)
          <td style="background: #F7FFBF;">PARCIAL</td> 
        @endif
        @if($sale->state_delivery == 3)
          <td style="background: #bbfccb;">COMPLETO</td> 
        @endif

        <td>{{ $sale->created_at->format("Y/m/d h:i A") }}</td>
      </tr>
    @endForeach
  </tbody>
</table>
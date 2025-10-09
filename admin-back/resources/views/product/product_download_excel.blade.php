<table>
  <thead>
    <tr>
      <th width="40">Producto</th>
      <th width="25">Código</th>
      <th width="25">Precio</th>
      {{-- <th width="25">Precio - cliente empresa</th> --}}
      <th width="25">Categoria</th>
      {{-- <th width="25">¿Es un regalo?</th> --}}
      <th width="20">¿Tiende descuento?</th>
      <th width="35">Tipo de impuesto</th>
      <th width="15">Inporte IVA</th>
      <th width="25">Disponibilidad</th>
      <th width="15">Estado</th>
      <th width="15">Dias de garantia</th>
    </tr>
  </thead>

  <tbody>
    @foreach ($products as $product)
      <tr>
        <td>{{ $product->title }}</td>
        <td>{{ $product->sku }}</td>
        <td>{{ $product->price_general }}</td>
        {{-- <td>{{ $product->price_company }}</td> --}}
        <td>{{ $product->category->title }}</td>
        {{-- <td>{{ $product->is_gift == 1 ? 'No' : 'Si' }}</td> --}}
        <td>
          {{ $product->is_discount == 1 ? 'No' : 'Si' }} <br />
          @if($product->is_discount != 1)
            <span>
              Descuento : {{ $product->max_descount }} $
            </span>
          @endIf
        </td>
        <td>
          @php 
            $tax_name = '';
          @endphp

          @switch($product->tax_selected)
            @case(1)
                {{ $tax_name = 'Sujeto a Impuesto'; }}
              @break
            @case(2)
                {{ $tax_name = 'Libre de Impuesto'; }}
              @break

          @endswitch

          {{ $tax_name }}

        </td>
        <td>{{ $product->importe_iva }} %</td>
        <td>
           @php 
            $available = '';
          @endphp

          @switch($product->available)
            @case(1)
                {{ $available = 'Vender sin Stock'; }}
              @break
            @case(2)
                {{ $available = 'No vender sin Stock'; }}
              @break

          @endswitch

          {{ $available }}
        </td>
        <td>{{ $product->status }}</td>
        <td>{{ $product->warranty_day }} dias</td>
      </tr>
    @endForeach
  </tbody>
</table>
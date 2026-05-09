# Reporte QA Local - Sprint 1 (API)

Fecha: 2026-04-08  
Entorno: local (`127.0.0.1:8000/api`)  
Frontend activo: `localhost:5173` (no usado para validacion principal)  
Usuario QA: `superadmin@sitecsas.com`

## 1) Alcance validado

Se ejecuto validacion API exhaustiva de:

- auth (`login`, `me`, `logout`, invalidacion de token)
- compras + atencion de detalle
- ventas + pagos (alta, edicion, borrado, sobrepago)
- conversiones (alta, bloqueo por stock insuficiente, borrado/reversion)
- traslados (salida, recepcion, bloqueo recepcion sin salida)
- kardex por API

## 2) Dataset minimo QA creado

Tag de dataset: `QA-S1-20260408092453`

### Datos maestros creados

- Sucursal: `id=2`
- Warehouse origen: `id=1`
- Warehouse destino: `id=2`
- Unidad origen: `id=1`
- Unidad destino: `id=2`
- Categoria: `id=1`
- Proveedor: `id=1`
- Cliente: `id=1`
- Producto: `id=1`

### Datos transaccionales creados

- Compra: `id=1000` (detalle `id=1`)
- Venta: `id=1000`
- Conversion: `id=1000` (luego eliminada para probar reversion)
- Traslado: `id=1000` (detalle `id=1`)

## 3) Pruebas ejecutadas y resultados

### Auth / sesion

- `POST /auth/login` -> OK
- `POST /auth/me` con token activo -> OK
- `POST /auth/logout` -> OK
- `POST /auth/me` con token invalidado -> OK (`401` esperado)
- `POST /auth/login` nuevamente -> OK

### Setup dataset (precondiciones)

- Creacion de sucursal, warehouses, units, categoria, proveedor, cliente y producto -> OK

### Compras + atencion detalle

- `POST /pushases` (alta compra) -> OK
- `POST /pushases/index` (recuperar compra/ids) -> OK
- `POST /pushase-details/attention` (primera atencion) -> OK
- `POST /pushase-details/attention` (reintento sobre detalle ya atendido) -> funcionalmente bloquea, pero ver BUG-01 (HTTP)

### Ventas + pagos

- `POST /sales` (alta venta) -> OK
- `GET /sales/{id}` -> OK
- `POST /sale-payments` (alta pago adicional) -> OK
- `PUT /sale-payments/{id}` (edicion pago) -> OK
- `DELETE /sale-payments/{id}` (borrado pago) -> OK
- `POST /sale-payments` (intento sobrepago) -> funcionalmente bloquea, pero ver BUG-02 (HTTP)

Evidencia de totales de pago:

- luego de alta: `payment_total=150`
- luego de edicion: `payment_total=170`
- luego de borrado: `payment_total=50`

### Conversiones

- `POST /conversions` (alta valida) -> OK
- `POST /conversions` (stock insuficiente) -> funcionalmente bloquea, pero ver BUG-03 (HTTP)
- `DELETE /conversions/{id}` (borrado + reversion) -> OK

Evidencia de reversion de stock:

- stock WH1/U1 antes conversion: `6`
- stock WH1/U1 despues de eliminar conversion: `6`
- stock WH1/U2 despues de eliminar conversion: `0`

### Traslados

- `POST /transports` (alta traslado) -> OK
- `POST /transports/index` (recuperar ids) -> OK
- `POST /transport-details/attention-delivery` sin salida previa -> funcionalmente bloquea, pero ver BUG-04 (HTTP)
- `POST /transport-details/attention-exit` (salida) -> OK
- `POST /transport-details/attention-delivery` (recepcion) -> OK

### Kardex

- `POST /kardex-product` -> OK
- Conteo de items devueltos en `data`: `1`

## 4) Bugs encontrados

### BUG-01 - Compra detalle ya atendido devuelve HTTP 200

- Endpoint: `POST /pushase-details/attention`
- Escenario: reintentar atencion de detalle previamente atendido
- Resultado actual: HTTP `200` con body `{ status: 403, message: ... }`
- Esperado: HTTP `403`

### BUG-02 - Sobrepago devuelve HTTP 200

- Endpoint: `POST /sale-payments`
- Escenario: registrar pago que supera total de venta
- Resultado actual: HTTP `200` con body `{ status: 403, message: ... }`
- Esperado: HTTP `403`

### BUG-03 - Conversion sin stock devuelve HTTP 200

- Endpoint: `POST /conversions`
- Escenario: convertir cantidad mayor al stock disponible
- Resultado actual: HTTP `200` con body `{ status: 403, message: ... }`
- Esperado: HTTP `403`

### BUG-04 - Recepcion sin salida devuelve HTTP 200

- Endpoint: `POST /transport-details/attention-delivery`
- Escenario: recepcion de detalle en estado pendiente (sin salida)
- Resultado actual: HTTP `200` con body `{ status: 403, message: ... }`
- Esperado: HTTP `403`

## 5) Cleanup

- No se ejecuto cleanup destructivo de transacciones (`compra`, `venta`, `traslado`) porque forman parte de evidencia QA y algunas quedan bloqueadas por reglas de dominio para borrado posterior.
- El dataset fue creado en base local de desarrollo unicamente, segun restriccion.

## 6) Nota tecnica importante

Durante la validacion, los bloqueos de negocio funcionan en logica funcional (status interno en body), pero no estandarizan codigo HTTP de error en varios endpoints. Esto puede romper clientes que dependen del status HTTP para control de flujo.

## 7) Retest Sprint 1.2 - Parche HTTP 403

Fecha: 2026-04-08  
Entorno: local (`127.0.0.1:8000/api`)  
Resultado: aplicado y verificado.

### Verificacion puntual antes/despues

- `POST /pushase-details/attention` (re-atencion): antes `HTTP 200` + body `status=403`; despues `HTTP 403` + body compatible.
- `POST /sale-payments` (sobrepago): antes `HTTP 200` + body `status=403`; despues `HTTP 403` + body compatible.
- `POST /conversions` (stock insuficiente): antes `HTTP 200` + body `status=403`; despues `HTTP 403` + body compatible.
- `POST /transport-details/attention-delivery` (recepcion sin salida): antes `HTTP 200` + body `status=403`; despues `HTTP 403` + body compatible.

### Estado final Sprint 1 (API)

- BUG-01: resuelto en Sprint 1.2.
- BUG-02: resuelto en Sprint 1.2.
- BUG-03: resuelto en Sprint 1.2.
- BUG-04: resuelto en Sprint 1.2.

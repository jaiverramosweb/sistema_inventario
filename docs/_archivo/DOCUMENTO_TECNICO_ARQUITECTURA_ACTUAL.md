# Documento Tecnico del Sistema (Estado Actual)

## Portada
- Proyecto: Sistema de Inventario Pro
- Alcance: Arquitectura actual implementada (backend Laravel API + frontend Vue SPA)
- Fecha: 2026-03-23
- Audiencia: Equipo de desarrollo, nuevos integrantes, stakeholders tecnicos
- Repositorio analizado: raiz del proyecto (`admin-back` y `admin-front`)

---

## 0. Resumen ejecutivo

El sistema implementa una arquitectura desacoplada con:

- Backend API REST en Laravel 12 (`admin-back`)
- Frontend SPA en Vue 3 + Vuetify (`admin-front`)
- Autenticacion JWT con segundo factor (TOTP + recovery codes)
- Autorizacion RBAC con Spatie Permission (roles y permisos)

Estado funcional actual:

- Core operativo: inventario, ventas, compras, traslados, devoluciones, conversiones, kardex, KPI, CRM, reacondicionamiento
- Integracion front/back estable por HTTP JSON
- Pipeline de despliegue en VPS con GitHub Actions + script de releases

Riesgos tecnicos relevantes:

- Inconsistencias de naming historicas (`puchase`, `sucuarsal`, `impote`, etc.)
- Validaciones backend heterogeneas en algunos flujos criticos
- Bugs puntuales en conversiones/traslados/reacondicionamiento (detallados en este documento)

---

## 1. Descripcion general del sistema

### 1.1 Proposito
Centralizar la operacion comercial y logistica de una organizacion con multiples sucursales y bodegas, incluyendo control de stock, ventas, compras, traslados, devoluciones, reportes y procesos tecnicos de reacondicionamiento.

### 1.2 Problema que resuelve
- Falta de trazabilidad de movimientos de inventario
- Gestion dispersa por sucursal y bodega
- Dificultad para controlar estados de entrega/pago
- Falta de costo tecnico acumulado en equipos reacondicionados
- Baja visibilidad de indicadores comerciales integrados

### 1.3 Alcance funcional actual
- Seguridad y acceso: login JWT, 2FA, roles/permisos
- Configuracion: sucursales, bodegas, categorias, proveedores, unidades, conversiones de unidad
- Operacion comercial: clientes, ventas, pagos, devoluciones
- Operacion de abastecimiento: compras y recepcion por detalle
- Logistica interna: traslados con salida y entrega por detalle
- Inventario: productos, stock por bodega/unidad, precios por sucursal/tipo cliente
- Analitica: dashboard KPI
- CRM: leads, oportunidades, etapas de pipeline, actividades
- Taller tecnico: workbench de reacondicionamiento

---

## 2. Arquitectura del sistema

### 2.1 Estilo arquitectonico
Arquitectura web desacoplada en dos servicios:

- `admin-back`: API REST stateless
- `admin-front`: SPA que consume API

### 2.2 Diagrama tecnico (texto)

```text
[Usuario navegador]
        |
        v
[Vue 3 SPA - admin-front]
  - Vue Router (file-based)
  - Vuetify UI
  - Guard de ruta por token/permisos
  - Cliente HTTP ($api/ofetch)
        |
        | HTTPS JSON + Bearer JWT
        v
[Laravel 12 API - admin-back]
  - routes/api.php
  - Controladores por modulo
  - Policies + middleware permission
  - Servicios (2FA, Refurbish)
  - Eloquent Models/Resources
        |
        v
[PostgreSQL]
  - inventario
  - comercial
  - logistica
  - CRM
  - seguridad
```

### 2.3 Flujo de comunicacion front-back
1. El frontend llama endpoints API con `VITE_API_BASE_URL`.
2. Agrega `Authorization: Bearer <token>` desde `localStorage`.
3. Laravel valida `auth:api` (driver JWT).
4. Se aplican gates/policies y middleware de permisos.
5. La API retorna JSON (frecuentemente via Resources).
6. La SPA actualiza vistas/tablas/dialogos.

### 2.4 Autenticacion implementada
- Driver activo: JWT (`config/auth.php`, guard `api`)
- Endpoints auth en `/api/auth/*`
- 2FA implementado con TOTP y recovery codes:
  - setup QR/manual key
  - verify challenge
  - disable y regeneracion de recovery codes
- Rate limiters:
  - `login`
  - `mfa_challenge`
  - `mfa_settings`

---

## 3. Estructura del proyecto

### 3.1 Backend (`admin-back`)

Estructura por capas y modulo:

- `routes/api.php`: definicion de endpoints
- `app/Http/Controllers/*`: controladores por dominio
- `app/Models/*`: entidades y relaciones Eloquent
- `app/Http/Requests/*`: validaciones de entrada (parcial)
- `app/Http/Resources/*`: formato de salida JSON
- `app/Policies/*`: autorizacion CRUD
- `app/Services/*`: logica de negocio especializada
- `database/migrations/*`: esquema de datos
- `database/seeders/*`: datos base

Separacion de responsabilidades real:

- Fuerte separacion por modulo
- Uso de `Resource` para serializacion consistente
- Uso de `Policy/Gate` en modulos principales
- Parte de logica de negocio sigue en controladores (especialmente transaccional)

Patrones observados:

- Service Layer (parcial): `TwoFactorService`, `RefurbishService`
- Policy-based access control
- Resource transformers
- No se identifica Repository Pattern formal

### 3.2 Frontend (`admin-front`)

Estructura principal:

- `src/pages/*`: vistas/rutas por funcionalidad
- `src/components/inventory/*`: componentes por dominio
- `src/plugins/1.router/*`: router y guards
- `src/utils/api.js`: cliente HTTP principal
- `src/composables/useApi.js`: wrapper alterno de fetch
- `src/navigation/vertical/index.js`: menu por permisos
- `src/plugins/2.pinia.js`: Pinia habilitado

Manejo de estado:

- Principalmente estado local por vista + `localStorage`
- Pinia existe, pero no hay stores de negocio relevantes actualmente

Organizacion de componentes:

- Alta cohesion por modulo: `sale`, `purchase`, `transport`, `product`, `crm`, `refurbish`, etc.
- Patrons de UI basados en dialogs y tablas Vuetify

---

## 4. Tecnologias utilizadas

### 4.1 Backend
- PHP `^8.2`
- Laravel `^12.0`
- JWT: `php-open-source-saver/jwt-auth`
- RBAC: `spatie/laravel-permission`
- Excel: `maatwebsite/excel`
- PDF: `barryvdh/laravel-dompdf`
- 2FA/TOTP: `pragmarx/google2fa`, `bacon/bacon-qr-code`
- DB: PostgreSQL

### 4.2 Frontend
- Vue `3.4.25`
- Vue Router `4.3.2`
- Vuetify `3.5.15`
- Pinia `2.1.7`
- Vite `5.2.10`
- HTTP: `ofetch`
- Charts: `apexcharts`, `chart.js`

### 4.3 DevOps
- GitHub Actions para CI/CD
- Script `deploy.sh` para releases en VPS
- Docker Compose para frontend (dev/prod)

---

## 5. Base de datos

### 5.1 Modelo de datos (macro)

Seguridad:
- `users`
- `roles`, `permissions` y pivotes Spatie
- `user_recovery_codes`
- columnas 2FA en `users`

Configuracion:
- `sucursales`, `warehouses`
- `categories`, `providers`
- `units`, `unit_conversions`

Inventario:
- `products`
- `product_warehouses` (stock por bodega/unidad)
- `product_wallets` (precio por tipo cliente/sucursal/unidad)
- `product_stock_initials` (base kardex)

Comercial y operaciones:
- `clients`
- `sales`, `sale_details`, `sale_payments`, `sale_detail_attentions`
- `refound_products`
- `puchases`, `puchase_details`
- `transports`, `transport_details`
- `conversions`

Tecnico:
- `product_items` (equipo-componente)
- `refurbish_history`

CRM:
- `leads`, `pipeline_stages`, `opportunities`, `crm_activities`

### 5.2 Relaciones de negocio clave
- Usuario -> rol + sucursal
- Producto -> categoria + stock (warehouse/unit) + precios (wallet)
- Venta -> cliente + detalles + pagos
- Compra -> detalles y recepcion de stock
- Traslado -> detalles con salida y entrega
- Devolucion -> referencia a detalle de venta
- Reacondicionamiento -> historial tecnico/economico por equipo
- Oportunidad CRM -> lead/cliente + etapa pipeline

### 5.3 Observaciones de esquema
- Hay varias llaves foraneas por `bigInteger` sin constraint formal en tablas legacy.
- El modulo CRM si incorpora `foreignId()->constrained()` de forma mas estricta.

---

## 6. APIs y endpoints

Base: `/api`

### 6.1 Catalogo funcional resumido

Auth y perfil:
- `POST /auth/login`
- `POST /auth/2fa/verify`
- `POST /auth/2fa/recovery`
- `POST /auth/logout`
- `POST /auth/refresh`
- `POST /auth/me`
- `POST /auth/profile/update`
- `GET /auth/2fa/status`
- `POST /auth/2fa/setup/init`
- `POST /auth/2fa/setup/verify`
- `POST /auth/2fa/disable`
- `POST /auth/2fa/recovery/regenerate`

Accesos/configuracion:
- `resource /role`
- `resource /users` + `GET /users/config`
- `resource /sucursales`
- `resource /warehouses`
- `resource /categories`
- `resource /providers`
- `resource /units`
- `resource /unit-conversions`

Inventario/comercial:
- `resource /products` + rutas auxiliares (`config`, `search_product`, `import-excel`)
- `resource /product-warehouse`
- `resource /product-wallet`
- `resource /clients`
- `resource /sales`, `resource /sale-details`, `resource /sale-payments`
- `resource /refound-products`

Abastecimiento/logistica:
- `resource /pushases`, `resource /pushase-details`
- `resource /transports`, `resource /transport-details`
- rutas de atencion de detalle en compra/traslado

Analitica/inventario avanzado:
- KPI (`/kpi/*`)
- Kardex (`POST /kardex-product`)
- Conversiones (`resource /conversions`)

CRM:
- `resource /crm/leads`
- `POST /crm/leads/{lead}/convert`
- `resource /crm/opportunities`
- `POST /crm/opportunities/{opportunity}/change-stage`
- `GET/POST/PUT/DELETE /crm/pipeline-stages`
- `resource /crm/activities`

Reacondicionamiento:
- `GET /refurbish/equipment/{id}`
- `POST /refurbish/add-component`
- `POST /refurbish/remove-component`
- `POST /refurbish/remove-unregistered`
- `POST /refurbish/finish/{id}`

### 6.2 Estructuras request/response (reales)

Login exitoso:

```json
{
  "access_token": "...",
  "token_type": "bearer",
  "expires_in": 57600,
  "user": {
    "id": 1,
    "full_name": "...",
    "email": "...",
    "role": { "id": 1, "name": "Super-Admin" },
    "permissions": ["list_product", "list_sale"],
    "two_factor_enabled": true
  }
}
```

Login con 2FA requerida:

```json
{
  "requires_2fa": true,
  "mfa_token": "...",
  "message": "Two-factor authentication required."
}
```

---

## 7. Flujos principales del sistema

### 7.1 Login con 2FA
1. Usuario envia credenciales a `/auth/login`.
2. Si no tiene 2FA: recibe JWT.
3. Si tiene 2FA: recibe `mfa_token`.
4. Front redirige a `login-2fa`.
5. Verifica con TOTP o recovery code.
6. Backend emite JWT final.

### 7.2 Venta
1. Front carga configuracion (`/sales/config`) y busca cliente/producto.
2. Arma detalle de venta y pagos.
3. `POST /sales`.
4. Backend crea venta+detalles+pagos en transaccion.
5. Si estado venta activa, descuenta stock y registra atencion por detalle.

### 7.3 Compra
1. Front crea compra (`/pushases`).
2. Recepcion de detalle con `/pushase-details/attention`.
3. Backend incrementa stock en `product_warehouses`.

### 7.4 Traslado
1. Se crea solicitud (`/transports`).
2. Salida por detalle (`attention-exit`): descuenta origen.
3. Entrega por detalle (`attention-delivery`): incrementa destino.
4. Estado general evoluciona por estados de detalle.

### 7.5 Reacondicionamiento
1. Carga equipo y configuracion actual (`/refurbish/equipment/{id}`).
2. Instala o retira componentes.
3. Ajusta `refurbished_value` segun operacion.
4. Registra auditoria en `refurbish_history`.
5. Finaliza equipo para venta.

### 7.6 CRM
1. Gestion de leads (`/crm/leads`).
2. Conversion lead -> cliente (+ oportunidad opcional).
3. Pipeline kanban de oportunidades por etapa.
4. Registro de actividades comerciales.

---

## 8. Frontend (UX, navegacion, validaciones)

### 8.1 Vistas principales
- `login`, `login-2fa`, `perfil`
- `dashboard`
- modulos de `product`, `client`, `sales`, `purchase`, `transport`, `refound`
- `kardex`, `conversion`, `refurbish`
- `crm/leads`, `crm/pipeline`
- `users`, `roles-permisos`, `configuration/*`

### 8.2 Navegacion y control de acceso
- Menu vertical por permisos (`src/navigation/vertical/index.js`)
- Guard global (`src/plugins/1.router/guards.js`):
  - valida sesion
  - aplica `meta.permission`
  - redirige a `not-authorized` o `login`

### 8.3 Formularios y validacion
- Validaciones en UI por reglas manuales y mensajes con `VAlert`
- Backend valida en paralelo (FormRequest o `validate`) segun modulo
- Flujo de dialogs para CRUD granular en tablas

---

## 9. Estado actual del proyecto

### 9.1 Funcionalidades implementadas
- Plataforma ERP liviana con cobertura operativa amplia
- Seguridad robusta con JWT + 2FA
- RBAC funcional en frontend y backend
- Integracion CRM inicial operativa
- Modulo tecnico de reacondicionamiento funcional

### 9.2 Pendientes o deuda tecnica visible
- Naming inconsistente heredado en varias entidades/campos/rutas
- Ausencia de suite de pruebas de negocio (solo tests ejemplo Laravel)
- Algunas vistas de template aun presentes (`index.vue`, `products.vue`)

### 9.3 Bugs y limitaciones conocidas (codigo actual)
- `ConversionController::index` usa `oderBy` (typo)
- `ConversionController::store` usa `auth('api')->user->id` (acceso incorrecto)
- `TransportDetailController::attentionDelivery` puede usar variable nula luego de `create`
- Ruta `POST /refurbish/start/{id}` declarada sin metodo `start` implementado
- Error en frontend `transport/edit/[id].vue`: uso de `index` no definido al eliminar detalle

---

## 10. Seguridad

### 10.1 Controles implementados
- JWT en guard `api`
- 2FA TOTP + recovery codes de un solo uso
- Rate limiting en login y MFA
- Policies/Gate para entidades sensibles
- Middleware de permisos por modulo

### 10.2 Riesgos y mejoras
- Token en `localStorage` (expuesto a XSS)
- Logout frontend actual limpia localStorage, pero no siempre llama `/auth/logout`
- Endurecer validaciones de payload y reglas de negocio en endpoints criticos

---

## 11. Despliegue

### 11.1 Backend
Estrategia de releases con symlink:

1. Crear release timestamp
2. Enlazar `.env` y `storage` compartidos
3. `composer install`
4. `php artisan migrate --force`
5. cache de config/rutas/vistas
6. mover `current` a nuevo release

### 11.2 Frontend
- Build con `pnpm run build`
- Nginx sirve SPA con fallback a `index.html`

### 11.3 CI/CD
- Workflow en push a `main`
- Build backend/frontend
- Despliegue remoto via SSH y ejecucion de `deploy.sh`

### 11.4 Variables de entorno principales

Backend:
- `APP_ENV`, `APP_URL`, `DB_*`, `JWT_SECRET`, `JWT_TTL`, `JWT_REFRESH_TTL`

Frontend:
- `VITE_API_BASE_URL`

---

## 12. Matriz de modulos y criticidad

Escala:
- Criticidad alta: afecta ingresos, stock, seguridad o continuidad operativa
- Criticidad media: afecta productividad o calidad operativa
- Criticidad baja: impacto limitado o periferico

| Modulo | Funcionalidad principal | Endpoints base | Criticidad |
|---|---|---|---|
| Auth + 2FA | Login, token, perfil, MFA | `/auth/*`, `/auth/2fa/*` | Alta |
| Roles/Usuarios | Control de accesos | `/role`, `/users` | Alta |
| Productos/Stock | Catalogo + existencias | `/products`, `/product-warehouse`, `/product-wallet` | Alta |
| Ventas | Venta, detalle, pagos | `/sales`, `/sale-details`, `/sale-payments` | Alta |
| Compras | Ingreso de mercancia | `/pushases`, `/pushase-details/attention` | Alta |
| Traslados | Movimiento entre bodegas | `/transports`, `/transport-details/*` | Alta |
| Devoluciones | Postventa y ajustes de stock/finanzas | `/refound-products/*` | Alta |
| Kardex | Trazabilidad de movimientos | `/kardex-product` | Alta |
| KPI Dashboard | Indicadores gerenciales | `/kpi/*` | Media |
| Conversiones | Cambio de unidad de inventario | `/conversions/*` | Media-Alta |
| Reacondicionamiento | Valor tecnico de equipos | `/refurbish/*` | Media-Alta |
| CRM | Leads y pipeline comercial | `/crm/*` | Media |
| Configuracion | Parametros maestros | `/sucursales`, `/warehouses`, etc. | Media |

---

## 13. Recomendaciones tecnicas

### 13.1 Corto plazo (0-4 semanas)
1. Corregir bugs identificados en conversiones, traslados y reacondicionamiento.
2. Estandarizar salida de errores y codigos HTTP.
3. Endurecer validaciones de requests de operaciones criticas.
4. Integrar logout frontend con endpoint `/auth/logout` de forma obligatoria.
5. Eliminar residuos de template no usados en produccion.

### 13.2 Mediano plazo (1-3 meses)
1. Extraer logica de negocio transaccional de controladores a services por modulo.
2. Introducir pruebas automatizadas de flujo de negocio (ventas, compras, traslados, devoluciones).
3. Reducir deuda tecnica de naming mediante estrategia incremental y compatibilidad.
4. Definir contrato API versionado (`/api/v1`) para evolucion segura.

### 13.3 Escalabilidad futura
1. Auditar cambios con event sourcing ligero o bitacora transversal.
2. Delegar calculos/reportes pesados a colas.
3. Fortalecer postura de seguridad frontend para tokens.
4. Consolidar modulo CRM con permisos dedicados y reportes de conversion.

---

## 14. Anexo: rutas de referencia del codigo

Backend principal:
- `admin-back/routes/api.php`
- `admin-back/bootstrap/app.php`
- `admin-back/config/auth.php`
- `admin-back/config/jwt.php`

Frontend principal:
- `admin-front/src/utils/api.js`
- `admin-front/src/plugins/1.router/guards.js`
- `admin-front/src/navigation/vertical/index.js`

DevOps:
- `deploy.sh`
- `.github/workflows/deploy.yml`

---

Fin del documento.

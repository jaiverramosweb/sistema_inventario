# Modelo de Base de Datos

> **Sistema de Inventario Pro** — Esquema relacional, diccionario de datos y diagramas.

---

## Tabla de contenidos

1. [Resumen](#1-resumen)
2. [Diagrama Entidad-Relación (alto nivel)](#2-diagrama-entidad-relación-alto-nivel)
3. [Convenciones](#3-convenciones)
4. [Módulo: Seguridad y acceso](#4-módulo-seguridad-y-acceso)
5. [Módulo: Configuración base](#5-módulo-configuración-base)
6. [Módulo: Catálogo de productos](#6-módulo-catálogo-de-productos)
7. [Módulo: Ventas](#7-módulo-ventas)
8. [Módulo: Compras](#8-módulo-compras)
9. [Módulo: Traslados](#9-módulo-traslados)
10. [Módulo: Devoluciones](#10-módulo-devoluciones)
11. [Módulo: Reacondicionamiento](#11-módulo-reacondicionamiento)
12. [Módulo: CRM](#12-módulo-crm)
13. [Módulo: Auditoría](#13-módulo-auditoría)
14. [Notas técnicas e inconsistencias](#14-notas-técnicas-e-inconsistencias)

---

## 1. Resumen

| Atributo | Valor |
|---|---|
| Motor | PostgreSQL 14+ |
| Codificación | UTF-8 |
| Tablas (~) | 60 |
| Migraciones | 50+ archivos en `database/migrations/` |
| Soft deletes | Sí (mayoría de tablas operativas) |
| Auditoría automática | Sí, vía `audit_events` y `audit_event_changes` |

---

## 2. Diagrama Entidad-Relación (alto nivel)

```
                              ┌────────────┐
                              │ sucursales │
                              └─────┬──────┘
                                    │
            ┌───────────────────────┼─────────────────────┐
            ▼                       ▼                     ▼
      ┌──────────┐            ┌────────────┐        ┌──────────┐
      │  users   │            │ warehouses │        │ clients  │
      └────┬─────┘            └──────┬─────┘        └─────┬────┘
           │                         │                    │
           │ rol (Spatie)            │                    │
           ▼                         │                    │
      ┌──────────┐                   │                    │
      │  roles   │                   │                    │
      │ permis.  │                   │                    │
      └──────────┘                   │                    │
                                     │                    │
                ┌────────────────────┼──────────┐         │
                ▼                    ▼          ▼         │
         ┌────────────┐      ┌───────────┐  ┌─────────┐   │
         │ categories │      │   units   │  │providers│   │
         └─────┬──────┘      └─────┬─────┘  └────┬────┘   │
               │                   │             │         │
               ▼                   │             │         │
        ┌──────────────┐           │             │         │
        │   products   │◄──────────┤             │         │
        └──────┬───────┘           │             │         │
               │                   │             │         │
   ┌───────────┴────────────┐      │             │         │
   ▼                        ▼      │             │         │
┌──────────────────┐  ┌────────────────┐         │         │
│product_warehouses│  │product_wallets │         │         │
└──────────────────┘  └────────────────┘         │         │
                                                  │         │
              ┌──────────────────────────────────┐│         │
              ▼                                  ▼▼         ▼
         ┌────────┐    ┌──────────┐    ┌─────────┐    ┌─────────┐
         │ sales  │    │ puchases │    │transports│   │  CRM    │
         └───┬────┘    └─────┬────┘    └────┬─────┘   │ leads/  │
             │               │              │         │ opps    │
             ▼               ▼              ▼         └─────────┘
      ┌────────────┐  ┌───────────────┐ ┌──────────────────┐
      │sale_details│  │puchase_details│ │transport_details │
      └────────────┘  └───────────────┘ └──────────────────┘

                  ┌──────────────────┐
                  │  audit_events    │ (registros de auditoría)
                  │  audit_event_changes
                  │  audit_exports
                  └──────────────────┘
```

---

## 3. Convenciones

- **PK**: `id` autoincremental (bigint) en todas las tablas.
- **Timestamps**: `created_at` y `updated_at` (excepto tablas pivot puras).
- **Soft deletes**: campo `deleted_at` (nullable). Permite auditoría histórica sin borrar físicamente.
- **FK**: `<entidad>_id` (snake_case). Ejemplo: `product_id`, `warehouse_id`.
- **Booleanos**: convención `is_*` (ej. `is_discount`).
- **Estados numéricos**: campo `state` o `status` con enteros documentados por tabla.
- **Nombres de tablas**: plural en inglés, snake_case (con algunas excepciones legacy ver §14).

---

## 4. Módulo: Seguridad y acceso

### 4.1 `users`

Usuarios del sistema. Se autentican con JWT y opcionalmente 2FA.

| Columna | Tipo | Null | Descripción |
|---|---|---|---|
| `id` | bigserial | NO | PK |
| `role_id` | bigint | YES | FK histórica a `roles` (legacy; el rol activo se maneja por Spatie) |
| `name` | varchar(255) | NO | Nombre completo |
| `email` | varchar(255) | NO | Único, login |
| `email_verified_at` | timestamp | YES | Fecha de verificación de correo |
| `password` | varchar(255) | NO | Hash bcrypt |
| `avatar` | varchar(255) | YES | Path/URL de avatar |
| `two_factor_enabled` | boolean | NO | 2FA activo |
| `two_factor_secret_encrypted` | text | YES | Secret TOTP cifrado |
| `two_factor_pending_secret_encrypted` | text | YES | Secret en proceso de configuración |
| `two_factor_confirmed_at` | timestamp | YES | Fecha de activación |
| `two_factor_last_used_step` | bigint | YES | Anti-replay para códigos TOTP |
| `created_at` / `updated_at` | timestamp | NO | — |
| `deleted_at` | timestamp | YES | Soft delete |

Índices: `email` (unique).
Relaciones: hasMany `sales`, `puchases`, `transports`, `clients`. Pertenece a roles vía Spatie.

### 4.2 `user_recovery_codes`

Códigos single-use para recuperar acceso si se pierde el dispositivo 2FA.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | FK → `users` |
| `code` | varchar | Código hasheado |
| `used_at` | timestamp | Fecha de uso (null = sin usar) |
| `created_at` | timestamp | — |

### 4.3 Tablas Spatie Permission

Estándar Spatie. No se modifican directamente; se gestionan vía API/seeders.

| Tabla | Propósito |
|---|---|
| `roles` | Catálogo de roles (Super-Admin, Vendedor, etc.) |
| `permissions` | 41 permisos del sistema |
| `model_has_roles` | Asignación rol ↔ usuario |
| `model_has_permissions` | Asignación permiso directo ↔ usuario (raro, normalmente vía rol) |
| `role_has_permissions` | Asignación permiso ↔ rol |

Guard usado: `api`.

---

## 5. Módulo: Configuración base

### 5.1 `sucursales`

Sucursales o sedes físicas de la empresa. (Ojo: nombre singular en español por convención original.)

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `name` | varchar | Nombre comercial |
| `address` | varchar | Dirección |
| `status` | enum/string | Activo / Inactivo |
| `created_at` / `updated_at` / `deleted_at` | timestamp | — |

### 5.2 `warehouses`

Almacenes físicos. Cada sucursal puede tener múltiples almacenes.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `sucursal_id` | bigint | FK → `sucursales` |
| `name` | varchar | Identificador del almacén |
| `address` | varchar | Dirección física |
| `status` | string | Activo / Inactivo |
| `created_at` / `updated_at` / `deleted_at` | timestamp | — |

### 5.3 `categories`

Categorías de productos.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `name` | varchar | Nombre |
| `status` | string | Activo / Inactivo |

### 5.4 `units`

Unidades de medida (Unidad, Kg, Litro, Caja, etc.).

### 5.5 `unit_conversions`

Conversiones entre unidades (Caja → Unidad con factor 12).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `from_unit_id` | bigint | Unidad origen |
| `to_unit_id` | bigint | Unidad destino |
| `factor` | decimal | Multiplicador |

### 5.6 `providers`

Proveedores de compra.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `name` | varchar | Razón social |
| `contact` | varchar | Persona de contacto |
| `email` | varchar | — |
| `phone` | varchar | — |
| `status` | string | Activo / Inactivo |

---

## 6. Módulo: Catálogo de productos

### 6.1 `products`

Productos comerciales y técnicos. Centro del modelo.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `category_id` | bigint | FK → `categories` |
| `title` | varchar | Nombre del producto |
| `imagen` | varchar | URL/path de imagen |
| `description` | text | Descripción larga |
| `price_general` | decimal(10,2) | Precio público |
| `price_company` | decimal(10,2) | Precio para empresas |
| `is_discount` | boolean | Tiene descuento |
| `max_descount` | decimal(5,2) | % de descuento máximo permitido |
| `is_gift` | boolean | Marca de promoción/regalo |
| `available` | boolean | Visible en catálogo |
| `status` | string | Activo / Inactivo |
| `tax_selected` | string | Tipo de impuesto aplicado |
| `importe_iva` | decimal | % de IVA aplicable |
| `status_stok` | smallint | 1=Disponible, 2=Por agotar, 3=Agotado |
| `warranty_day` | integer | Días de garantía |
| `sku` | varchar | Código SKU |
| `brand` | varchar | Marca |
| `model` | varchar | Modelo |
| `serial` | varchar | Serial (cuando aplica) |
| `part_number` | varchar | Número de parte |
| `internal_code` | varchar | Código interno |
| `equipment_type` | string | Tipo de equipo (para refurbish) |
| `condition_status` | string | Estado: nuevo, usado, refurbished |
| `refurbish_state` | string | Estado del proceso de reacondicionamiento |
| `base_cost` | decimal | Costo base |
| `refurbished_value` | decimal | Costo final post-reacondicionamiento |
| `technical_comments` | text | Notas técnicas |
| timestamps + `deleted_at` | — | — |

Índices recomendados: `sku`, `category_id`, `serial`, `internal_code`.

### 6.2 `product_warehouses`

Stock real de un producto en un almacén, expresado en una unidad.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `product_id` | bigint | FK → `products` |
| `warehouse_id` | bigint | FK → `warehouses` |
| `unit_id` | bigint | FK → `units` |
| `stock` | decimal | Cantidad disponible |
| `umbral` | decimal | Stock mínimo (alerta de reposición) |

> Permite que el mismo producto exista en varios almacenes con stock independiente.

### 6.3 `product_wallets`

Precios diferenciados por sucursal.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `product_id` | bigint | FK → `products` |
| `sucursal_id` | bigint | FK → `sucursales` |
| `price_general` | decimal | Precio general en esa sucursal |
| `price_company` | decimal | Precio empresa en esa sucursal |

### 6.4 `product_items`

Componentes instalados en un equipo (relación recursiva).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `parent_product_id` | bigint | Equipo principal |
| `child_product_id` | bigint | Componente instalado |

### 6.5 `product_stock_initials`

Stock inicial declarado al crear un producto (snapshot histórico).

---

## 7. Módulo: Ventas

### 7.1 `sales`

Encabezado de ventas o cotizaciones.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Vendedor (FK → `users`) |
| `client_id` | bigint | FK → `clients` |
| `type_client` | smallint | 1=Final, 2=Empresa |
| `sucursal_id` | bigint | FK → `sucursales` |
| `state` | smallint | 1=Venta, 2=Cotización |
| `state_mayment` | smallint | 1=Pendiente, 2=Parcial, 3=Total |
| `state_delivery` | smallint | Estado de entrega |
| `subtotal` | decimal | — |
| `iva` | decimal | — |
| `total` | decimal | — |
| `debt` | decimal | Saldo pendiente |
| `paid_out` | decimal | Total pagado |
| `date_validation` | date | Fecha de validación |
| `date_completed` | date | Fecha de cierre |
| `description` | text | Notas |
| timestamps + `deleted_at` | — | — |

### 7.2 `sale_details`

Líneas de una venta.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `sale_id` | bigint | FK → `sales` |
| `product_id` | bigint | FK → `products` |
| `product_categoryid` | bigint | Cache de categoría |
| `unit_id` | bigint | FK → `units` |
| `warehouse_id` | bigint | FK → `warehouses` (almacén origen) |
| `quantity` | decimal | Cantidad |
| `price_unit` | decimal | Precio unitario aplicado |
| `descuento` | decimal | Descuento por línea |
| `iva` | decimal | IVA por línea |
| `subtotal` | decimal | — |
| `total` | decimal | — |

### 7.3 `sale_payments`

Pagos parciales o totales de una venta.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `sale_id` | bigint | FK → `sales` |
| `amount` | decimal | Monto del pago |
| `payment_method` | string | Efectivo, transferencia, tarjeta, etc. |
| `reference` | varchar | Referencia bancaria |
| `paid_at` | timestamp | Fecha del pago |

### 7.4 `sale_detail_attentions`

Estados de atención por línea (para devoluciones/RMA).

---

## 8. Módulo: Compras

> **Nota legacy**: el nombre de la tabla es `puchases` (con typo). Documentado tal cual existe.

### 8.1 `puchases`

Encabezado de compras a proveedores.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `warehouse_id` | bigint | FK → `warehouses` (destino) |
| `user_id` | bigint | Comprador |
| `provider_id` | bigint | FK → `providers` |
| `sucuarsal_id` | bigint | FK → `sucursales` (typo: debería ser `sucursal_id`) |
| `date_emition` | date | Fecha de emisión |
| `state` | smallint | 1=Solicitud, 2=Parcial, 3=Entregado |
| `type_comprobant` | string | Factura, remisión, etc. |
| `n_comprobant` | varchar | Número de comprobante |
| `total` | decimal | Total |
| `immporte` | decimal | Subtotal (typo: debería `importe`) |
| `iva` | decimal | — |
| `date_delivery` | date | Fecha de entrega prevista |
| `date_exit` | date | Fecha de despacho |
| timestamps + `deleted_at` | — | — |

### 8.2 `puchase_details`

Líneas de compra.

---

## 9. Módulo: Traslados

### 9.1 `transports`

Movimientos de stock entre almacenes.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Solicitante |
| `warehause_start_id` | bigint | Almacén origen (typo: `warehause`) |
| `warehause_end_id` | bigint | Almacén destino |
| `date_emision` | date | Fecha solicitud |
| `state` | smallint | 1=Solicitud, 2=Revisión salida, 3=Salida, 4=Llegada, 5=Revisión llegada, 6=Entrega |
| `impote` | decimal | Subtotal (typo) |
| `iva` | decimal | — |
| `total` | decimal | — |
| `date_delivery` | date | Fecha entrega |
| timestamps + `deleted_at` | — | — |

### 9.2 `transport_details`

Líneas de un traslado, con sub-estados para revisión de salida y entrega.

---

## 10. Módulo: Devoluciones

### 10.1 `refound_products`

Devoluciones, reparaciones y reemplazos vinculados a una venta.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Asesor que registra |
| `client_id` | bigint | FK → `clients` |
| `sale_detail_id` | bigint | FK → `sale_details` (línea original) |
| `product_id` | bigint | FK → `products` |
| `unit_id` | bigint | FK → `units` |
| `warehouse_id` | bigint | Almacén receptor |
| `quantity` | decimal | — |
| `type` | smallint | 1=Reparación, 2=Reemplazo, 3=Devolución |
| `state` | smallint | 0=Sin estado, 1=Pendiente, 2=Revisión, 3=Reparado, 4=Descartado |
| `resoslution_date` | date | Fecha de resolución (typo: `resolution`) |
| `resoslution_description` | text | Notas de resolución |

---

## 11. Módulo: Reacondicionamiento

### 11.1 `refurbish_histories`

Historial de trabajos sobre un equipo refurbished. Cada entrada documenta una operación (instalación de pieza, retiro, finalización).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `product_id` | bigint | Equipo intervenido |
| `user_id` | bigint | Técnico responsable |
| `action` | string | start / add_component / remove_component / finish |
| `component_id` | bigint | FK → `products` (componente involucrado) |
| `cost_delta` | decimal | Variación de costo aplicada |
| `notes` | text | Comentarios técnicos |
| `created_at` | timestamp | — |

> El cálculo del costo final se aplica sumando los `cost_delta` al `base_cost` del producto y se persiste en `refurbished_value`.

---

## 12. Módulo: CRM

### 12.1 `clients`

Clientes finales y empresariales.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `name` | varchar | Nombre |
| `surname` | varchar | Apellido |
| `email` | varchar | — |
| `phone` | varchar | — |
| `type_client` | smallint | 1=Final, 2=Empresa |
| `type_document` | string | CC, NIT, Pasaporte |
| `n_document` | varchar | Número de documento |
| `id_department` / `department` | bigint/varchar | Departamento (FK + cache de nombre) |
| `id_municipality` / `municipality` | bigint/varchar | Municipio |
| `id_district` / `district` | bigint/varchar | Distrito |
| `address` | varchar | Dirección |
| `date_birthday` | date | Fecha de nacimiento |
| `gender` | string | M/F/O |
| `user_id` | bigint | Quién registró |
| `sucursal_id` | bigint | Sucursal asignada |
| `status` | smallint | 1=Activo, 2=Inactivo |
| timestamps + `deleted_at` | — | — |

### 12.2 `leads`

Prospectos (antes de venta).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Asesor asignado |
| `name`, `email`, `phone` | varchar | Datos de contacto |
| `company` | varchar | Empresa |
| `source` | string | web, referido, llamada, evento |
| `status` | string | nuevo, contactado, calificado, descartado |
| `probability` | smallint | % probabilidad de conversión |
| timestamps + `deleted_at` | — | — |

### 12.3 `opportunities`

Oportunidades de negocio en progreso.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Asesor |
| `lead_id` | bigint | FK → `leads` |
| `pipeline_stage_id` | bigint | FK → `pipeline_stages` |
| `title` | varchar | — |
| `description` | text | — |
| `value` | decimal | Valor estimado |
| `expected_close_date` | date | — |
| `status` | string | abierta, ganada, perdida |

### 12.4 `pipeline_stages`

Etapas configurables del embudo (Kanban).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `name` | varchar | — |
| `order` | int | Orden de visualización |
| `color` | varchar | Color para UI |
| `status` | string | Activa/Inactiva |

### 12.5 `crm_activities`

Actividades de seguimiento (llamadas, emails, reuniones, tareas).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Responsable |
| `opportunity_id` | bigint | FK → `opportunities` |
| `type` | string | call, email, meeting, task |
| `description` | text | — |
| `scheduled_date` | timestamp | Fecha programada |

---

## 13. Módulo: Auditoría

### 13.1 `audit_events`

Cada acción registrable genera un evento.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `auditable_type` | string | Clase del modelo afectado (ej. `App\Models\Sale`) |
| `auditable_id` | bigint | ID del modelo afectado |
| `action` | string | created, updated, deleted, viewed, exported, etc. |
| `actor_type` | string | `App\Models\User` |
| `actor_id` | bigint | ID del usuario que realizó la acción |
| `description` | text | Descripción legible |
| `ip_address` | inet | IP del cliente |
| `user_agent` | text | User-Agent |
| `created_at` | timestamp | — |

### 13.2 `audit_event_changes`

Diff de campos modificados.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `audit_event_id` | bigint | FK → `audit_events` |
| `field` | varchar | Nombre del campo |
| `old_value` | text | Valor anterior |
| `new_value` | text | Valor nuevo |

### 13.3 `audit_exports`

Registra cuándo y quién exportó logs.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigserial | PK |
| `user_id` | bigint | Quién exportó |
| `type` | string | xlsx / csv |
| `filters` | json | Filtros aplicados |
| `file_path` | varchar | Ruta del archivo generado |
| `created_at` | timestamp | — |

---

## 14. Notas técnicas e inconsistencias

### 14.1 Typos legacy en nombres

| Tabla / Columna | Debe ser | Razón |
|---|---|---|
| `puchases` | `purchases` | Typo histórico |
| `puchase_details` | `purchase_details` | — |
| `immporte` | `importe` | — |
| `impote` | `importe` | — |
| `warehause_start_id` / `warehause_end_id` | `warehouse_*` | — |
| `sucuarsal_id` | `sucursal_id` | — |
| `state_mayment` | `state_payment` | — |
| `resoslution_date` / `resoslution_description` | `resolution_*` | — |

> **Recomendación**: NO renombrar en producción (rompe queries y código). Documentar y planear una migración con doble lectura/escritura si se desea limpiar.

### 14.2 Soft deletes

Todas las tablas operativas (productos, ventas, compras, traslados, clientes, usuarios) tienen `deleted_at`. Esto permite:
- Reportes históricos sin pérdida de información
- Recuperación de registros borrados accidentalmente

> Las consultas estándar de Eloquent excluyen automáticamente los registros con `deleted_at`. Para incluirlos: `Model::withTrashed()->...`.

### 14.3 Auditoría: privacidad y volumen

`audit_events` y `audit_event_changes` crecen rápido en producción. Recomendaciones:
- Archivar registros mayores a 12 meses a almacenamiento frío
- No guardar valores de campos sensibles (`password`, `two_factor_secret_*`) — verificar en el observer
- Configurar retention policy explícita en `config/audit.php` (si existe) o vía comando programado

### 14.4 Índices recomendados (no aplicados todavía)

| Tabla | Campos | Razón |
|---|---|---|
| `products` | `sku`, `serial`, `internal_code` | Búsquedas por código |
| `sales` | `(client_id, created_at)` | Reportes por cliente |
| `sale_details` | `(sale_id, product_id)` | Joins frecuentes |
| `audit_events` | `(actor_id, created_at)` | Filtros del visor |
| `audit_events` | `(auditable_type, auditable_id)` | Trazabilidad por entidad |
| `product_warehouses` | `(product_id, warehouse_id)` UNIQUE | Evitar duplicados |

### 14.5 Convención de estados (`state` numéricos)

El sistema usa enteros para máquinas de estado. La equivalencia se mantiene en código (controladores y front). **Recomendación a largo plazo**: migrar a `enum` nativos de PostgreSQL o tablas catálogo para autodocumentación.

| Tabla | Campo | Valores |
|---|---|---|
| `sales` | `state` | 1=Venta, 2=Cotización |
| `sales` | `state_mayment` | 1=Pendiente, 2=Parcial, 3=Total |
| `puchases` | `state` | 1=Solicitud, 2=Parcial, 3=Entregado |
| `transports` | `state` | 1=Solicitud, 2=Revisión salida, 3=Salida, 4=Llegada, 5=Revisión llegada, 6=Entrega |
| `refound_products` | `state` | 0=Sin estado, 1=Pendiente, 2=Revisión, 3=Reparado, 4=Descartado |
| `refound_products` | `type` | 1=Reparación, 2=Reemplazo, 3=Devolución |
| `clients` | `status` | 1=Activo, 2=Inactivo |
| `products` | `status_stok` | 1=Disponible, 2=Por agotar, 3=Agotado |

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Configuración del sistema](./CONFIGURACION.md)
- [Arquitectura técnica](./ARQUITECTURA.md)
- Migraciones reales: `admin-back/database/migrations/`

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

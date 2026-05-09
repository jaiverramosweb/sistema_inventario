# API REST — Referencia

> **Sistema de Inventario Pro** — Catálogo de endpoints, convenciones y ejemplos.

---

## Tabla de contenidos

1. [Convenciones generales](#1-convenciones-generales)
2. [Autenticación](#2-autenticación)
3. [Códigos de respuesta](#3-códigos-de-respuesta)
4. [Auth y perfil](#4-auth-y-perfil)
5. [Usuarios y roles](#5-usuarios-y-roles)
6. [Configuración base](#6-configuración-base)
7. [Productos](#7-productos)
8. [Clientes](#8-clientes)
9. [Ventas](#9-ventas)
10. [Devoluciones](#10-devoluciones)
11. [Compras](#11-compras)
12. [Traslados](#12-traslados)
13. [Conversiones y Kardex](#13-conversiones-y-kardex)
14. [KPI / Dashboard](#14-kpi--dashboard)
15. [Reacondicionamiento](#15-reacondicionamiento)
16. [CRM](#16-crm)
17. [Auditoría](#17-auditoría)
18. [Reportes y exportación](#18-reportes-y-exportación)

---

## 1. Convenciones generales

| Atributo | Valor |
|---|---|
| Base URL (dev) | `http://127.0.0.1:8000/api` |
| Base URL (prod) | `https://api.sistema-inventario.com/api` |
| Formato | `application/json` |
| Encoding | UTF-8 |
| Auth | `Authorization: Bearer <JWT>` |
| Content-Type (POST/PUT) | `application/json` |
| Content-Type (uploads) | `multipart/form-data` |

### 1.1 Estructura de respuesta exitosa

Endpoints de tipo lista (paginados):

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 145
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

Endpoints de tipo detalle:

```json
{
  "data": {
    "id": 1,
    "...": "..."
  }
}
```

### 1.2 Estructura de respuesta de error

```json
{
  "message": "Mensaje legible",
  "errors": {
    "campo": ["Regla de validación violada"]
  }
}
```

---

## 2. Autenticación

Todos los endpoints, excepto `POST /auth/login` y `POST /auth/register`, requieren JWT:

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

El token expira a las 8 horas (`JWT_TTL=480`). Se puede refrescar dentro de 14 días con `POST /auth/refresh`.

---

## 3. Códigos de respuesta

| Código | Significado |
|---|---|
| 200 | OK |
| 201 | Recurso creado |
| 204 | OK, sin contenido |
| 400 | Petición inválida |
| 401 | No autenticado (token faltante o expirado) |
| 403 | Sin permiso |
| 404 | No encontrado |
| 409 | Conflicto (ej. recurso duplicado) |
| 422 | Validación falló |
| 429 | Rate limit excedido |
| 500 | Error del servidor |

---

## 4. Auth y perfil

| Método | Path | Descripción | Permiso | Throttle |
|---|---|---|---|---|
| POST | `/auth/register` | Registrar usuario | público | `login` |
| POST | `/auth/login` | Iniciar sesión | público | `login` |
| POST | `/auth/logout` | Cerrar sesión | `auth:api` | — |
| POST | `/auth/refresh` | Renovar token | `auth:api` | — |
| POST | `/auth/me` | Datos del usuario actual | `auth:api` | — |
| POST | `/auth/profile/update` | Actualizar perfil | `auth:api` | `mfa_settings` |

### 4.1 Login

**Request**:
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "superadmin@sitecsas.com",
  "password": "********"
}
```

**Response (sin 2FA)**:
```json
{
  "access_token": "eyJhbGciOi...",
  "token_type": "bearer",
  "expires_in": 28800,
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "superadmin@sitecsas.com",
    "role": { "id": 1, "name": "Super-Admin" },
    "permissions": ["dashboard","list_product","..."],
    "two_factor_enabled": false
  }
}
```

**Response (con 2FA habilitada)**:
```json
{
  "requires_2fa": true,
  "mfa_token": "...",
  "message": "Two-factor authentication required."
}
```

### 4.2 Endpoints 2FA

| Método | Path | Descripción | Throttle |
|---|---|---|---|
| GET | `/auth/2fa/status` | Estado actual de 2FA | `mfa_settings` |
| POST | `/auth/2fa/setup/init` | Iniciar configuración (devuelve QR + secret) | `mfa_settings` |
| POST | `/auth/2fa/setup/verify` | Confirmar configuración | `mfa_settings` |
| POST | `/auth/2fa/verify` | Verificar código en login | `mfa_challenge` |
| POST | `/auth/2fa/recovery` | Usar código de recuperación | `mfa_challenge` |
| POST | `/auth/2fa/disable` | Deshabilitar 2FA | `mfa_settings` |
| POST | `/auth/2fa/recovery/regenerate` | Regenerar códigos de recuperación | `mfa_settings` |

---

## 5. Usuarios y roles

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| GET | `/users` | Listado | `list_user` |
| GET | `/users/config` | Datos para crear (roles, sucursales) | `list_user` |
| POST | `/users` | Crear | `register_user` |
| PUT/POST | `/users/{id}` | Actualizar | `edit_user` |
| DELETE | `/users/{id}` | Eliminar (soft delete) | `delete_user` |
| GET | `/role` | Listado roles | `list_role` |
| POST | `/role` | Crear rol con permisos | `register_role` |
| PUT | `/role/{id}` | Actualizar rol | `edit_role` |
| DELETE | `/role/{id}` | Eliminar rol | `delete_role` |

---

## 6. Configuración base

CRUD estándar con permiso `settings`:

| Recurso | Path |
|---|---|
| Sucursales | `/sucursales` |
| Almacenes | `/warehouses` |
| Categorías | `/categories` |
| Proveedores | `/providers` |
| Unidades | `/units` |
| Conversiones de unidad | `/unit-conversions` |

Métodos: `GET` (listado), `POST` (crear), `PUT/PATCH /<id>` (actualizar), `DELETE /<id>` (eliminar).

---

## 7. Productos

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| POST | `/products/index` | Listado con filtros | `list_product` |
| GET | `/products/config` | Datos para crear (categorías, unidades) | `list_product` |
| GET | `/products/search_product?q=...` | Búsqueda rápida | `list_product` |
| POST | `/products` | Crear producto | `register_product` |
| GET | `/products/{id}` | Detalle | `list_product` |
| POST/PUT | `/products/{id}` | Actualizar | `edit_product` |
| DELETE | `/products/{id}` | Eliminar | `delete_product` |
| POST | `/products/import-excel` | Importar masivo (Excel) | `register_product` |
| GET | `/products-excel` | Exportar listado a Excel | `list_product` |
| GET/POST/PUT/DELETE | `/product-warehouse` | Stock por almacén | `show_inventory_product` |
| GET/POST/PUT/DELETE | `/product-wallet` | Precios por sucursal | `show_wallet_price_product` |

### 7.1 Crear producto (ejemplo)

```http
POST /api/products
Content-Type: application/json
Authorization: Bearer <token>

{
  "category_id": 3,
  "title": "Disco SSD 1TB",
  "sku": "SSD-1TB-001",
  "brand": "Kingston",
  "model": "SA400S37",
  "price_general": 350000,
  "price_company": 320000,
  "warranty_day": 365,
  "available": true,
  "status": "Activo",
  "tax_selected": "IVA",
  "importe_iva": 19
}
```

---

## 8. Clientes

CRUD estándar con permisos `register_client`, `list_client`, `edit_client`, `delete_client`:

```
GET    /clients
POST   /clients
GET    /clients/{id}
PUT    /clients/{id}
DELETE /clients/{id}
```

---

## 9. Ventas

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| POST | `/sales/index` | Listado con filtros | `list_sale` |
| GET | `/sales/config` | Datos para crear (clientes, almacenes, descuentos) | `list_sale` |
| GET | `/sales/search_client?q=...` | Buscar cliente | `list_sale` |
| POST | `/sales` | Crear venta o cotización | `register_sale` |
| GET | `/sales/{id}` | Detalle | `list_sale` |
| PUT | `/sales/{id}` | Actualizar | `edit_sale` |
| DELETE | `/sales/{id}` | Anular | `delete_sale` |
| GET/POST/PUT/DELETE | `/sale-details` | Líneas de venta | implícito |
| GET/POST/PUT/DELETE | `/sale-payments` | Pagos | implícito |
| POST | `/stock-attention-detail` | Atención de stock | `edit_sale` |

### 9.1 Crear venta (ejemplo simplificado)

```http
POST /api/sales
Content-Type: application/json

{
  "client_id": 12,
  "type_client": 1,
  "sucursal_id": 1,
  "state": 1,
  "subtotal": 500000,
  "iva": 95000,
  "total": 595000,
  "details": [
    {
      "product_id": 45,
      "warehouse_id": 1,
      "unit_id": 1,
      "quantity": 2,
      "price_unit": 250000,
      "descuento": 0,
      "iva": 47500,
      "total": 547500
    }
  ],
  "payments": [
    { "amount": 595000, "payment_method": "Efectivo" }
  ]
}
```

---

## 10. Devoluciones

Permiso `return`:

| Método | Path | Descripción |
|---|---|---|
| POST | `/refound-products/index` | Listado con filtros |
| GET | `/refound-products/search-sale/{id}` | Buscar por venta |
| POST | `/refound-products` | Crear devolución |
| GET | `/refound-products/{id}` | Detalle |
| PUT | `/refound-products/{id}` | Actualizar (cambiar estado/resolución) |
| DELETE | `/refound-products/{id}` | Anular |

---

## 11. Compras

> **Nota**: el path histórico es `/pushases` (typo) por compatibilidad.

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| POST | `/pushases/index` | Listado | `list_purchase` |
| GET | `/pushases/config` | Datos para crear | `list_purchase` |
| POST | `/pushases` | Crear | `register_purchase` |
| GET | `/pushases/{id}` | Detalle | `list_purchase` |
| PUT | `/pushases/{id}` | Actualizar | `edit_purchase` |
| DELETE | `/pushases/{id}` | Anular | `delete_purchase` |
| GET/POST/PUT/DELETE | `/pushase-details` | Líneas de compra | implícito |
| POST | `/pushase-details/attention` | Marcar línea como recibida | `edit_purchase` |
| GET | `/pushases-pdf/{id}` | PDF del comprobante | `list_purchase` |

---

## 12. Traslados

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| POST | `/transports/index` | Listado | `list_transport` |
| GET | `/transports/config` | Datos para crear | `list_transport` |
| POST | `/transports` | Crear traslado | `register_transport` |
| GET | `/transports/{id}` | Detalle | `list_transport` |
| PUT | `/transports/{id}` | Actualizar | `edit_transport` |
| DELETE | `/transports/{id}` | Anular | `delete_transport` |
| GET/POST/PUT/DELETE | `/transport-details` | Líneas | implícito |
| POST | `/transport-details/attention-exit` | Marcar salida (descuenta origen) | `edit_transport` |
| POST | `/transport-details/attention-delivery` | Marcar entrega (suma destino) | `edit_transport` |
| GET | `/transport-pdf/{id}` | PDF del traslado | `list_transport` |

---

## 13. Conversiones y Kardex

### 13.1 Conversiones

Permiso `conversions`:

```
POST   /conversions/index
GET    /conversions
POST   /conversions
GET    /conversions/{id}
PUT    /conversions/{id}
DELETE /conversions/{id}
```

### 13.2 Kardex

Permiso `kardex`:

```
POST /kardex-product
```

Body:
```json
{
  "product_id": 45,
  "warehouse_id": 1,
  "date_from": "2026-01-01",
  "date_to": "2026-05-09"
}
```

Devuelve la cronología de movimientos (entradas, salidas, traslados, ventas) con saldo acumulado.

---

## 14. KPI / Dashboard

Prefijo `/api/kpi/`. Permiso `dashboard`. Todos POST con filtros opcionales por sucursal y rango de fechas.

| Endpoint | Devuelve |
|---|---|
| `/information-general` | Total ventas, compras, clientes, productos |
| `/asesor-most-sale` | Top vendedores |
| `/sales-total-payment` | Ventas vs pagos por periodo |
| `/sucursales-report-sales` | Ventas por sucursal |
| `/client-most-sale` | Clientes con más compras |
| `/sales-x-month-year` | Serie temporal de ventas |
| `/category-most-sales` | Categorías más vendidas |

---

## 15. Reacondicionamiento

Prefijo `/api/refurbish/`:

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| GET | `/equipment/{id}` | Datos del equipo + componentes instalados | `list_refurbish` |
| POST | `/start/{id}` | Iniciar proceso | `register_refurbish` |
| POST | `/add-component` | Instalar componente desde inventario | `edit_refurbish` |
| POST | `/remove-component` | Retirar componente registrado | `edit_refurbish` |
| POST | `/remove-unregistered` | Quitar componente sin registro previo | `edit_refurbish` |
| POST | `/finish/{id}` | Finalizar y dejar disponible para venta | `edit_refurbish` |

---

## 16. CRM

Prefijo `/api/crm/`.

### 16.1 Leads

| Método | Path | Permiso |
|---|---|---|
| GET | `/leads` | `list_lead` |
| POST | `/leads` | `register_lead` |
| GET | `/leads/{id}` | `list_lead` |
| PUT | `/leads/{id}` | `edit_lead` |
| DELETE | `/leads/{id}` | `delete_lead` |
| POST | `/leads/{id}/convert` | `convert_lead` |

### 16.2 Oportunidades

| Método | Path | Permiso |
|---|---|---|
| GET | `/opportunities` | `list_opportunity` |
| POST | `/opportunities` | `register_opportunity` |
| GET | `/opportunities/{id}` | `list_opportunity` |
| PUT | `/opportunities/{id}` | `edit_opportunity` |
| DELETE | `/opportunities/{id}` | `delete_opportunity` |
| POST | `/opportunities/{id}/change-stage` | `edit_opportunity` |

### 16.3 Pipeline Stages

```
GET    /pipeline-stages
POST   /pipeline-stages
PUT    /pipeline-stages/{id}
DELETE /pipeline-stages/{id}
POST   /pipeline-stages/reorder
```
Permiso: `list_opportunity` para lectura, `edit_opportunity` para escritura.

### 16.4 Activities

```
GET    /activities      → list_crm_activity
POST   /activities      → register_crm_activity
PUT    /activities/{id} → edit_crm_activity
DELETE /activities/{id} → delete_crm_activity
```

---

## 17. Auditoría

Prefijo `/api/audit/`:

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| POST | `/navigation` | Registrar navegación frontend | `auth:api` |
| GET | `/logs` | Listado de eventos | `view_audit_logs` |
| GET | `/logs/filters` | Filtros disponibles | `view_audit_logs` |
| GET | `/logs/{id}` | Detalle con diff de campos | `view_audit_logs` |
| GET | `/logs/export?format=xlsx` | Exportar XLSX | `export_audit_logs` |
| GET | `/logs/export?format=csv` | Exportar CSV | `export_audit_logs` |

---

## 18. Reportes y exportación

| Método | Path | Descripción | Permiso |
|---|---|---|---|
| GET | `/products-excel` | Listado de productos en Excel | `list_product` |
| GET | `/sales-excel` | Listado de ventas en Excel | `list_sale` |
| GET | `/sales-pdf/{id}` | Comprobante de venta en PDF | `list_sale` |
| GET | `/pushases-pdf/{id}` | Comprobante de compra en PDF | `list_purchase` |
| GET | `/transport-pdf/{id}` | Guía de traslado en PDF | `list_transport` |

---

## Colección Postman / Insomnia

Disponible en: `entrega_cliente/recursos/sistema-inventario.postman_collection.json`

**Cómo usarla:**

1. Importar en Postman: File → Import → seleccionar el JSON.
2. Configurar variables de colección:
   - `base_url` (default `http://127.0.0.1:8000/api`)
   - `email` (default `superadmin@sitecsas.com`)
   - `password` (vacío — completar con el password real)
3. Ejecutar **Auth → Login** primero. El test script guarda automáticamente el `access_token` en `{{token}}`.
4. El resto de endpoints usan el token guardado.

**Cobertura**: Auth + 2FA, Productos, Clientes, Ventas, Compras, Traslados, Conversiones, Kardex, Refurbish, CRM, Auditoría, KPI, Configuración, Usuarios y Roles.

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Configuración del sistema](./CONFIGURACION.md)
- [Modelo de base de datos](./MODELO_BD.md)
- [Arquitectura](./ARQUITECTURA.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

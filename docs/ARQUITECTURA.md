# Arquitectura del Sistema

> **Sistema de Inventario Pro** — Visión técnica integral. Audiencia: arquitectos, desarrolladores y stakeholders técnicos.

---

## Tabla de contenidos

1. [Visión general](#1-visión-general)
2. [Estilo arquitectónico](#2-estilo-arquitectónico)
3. [Diagrama de componentes](#3-diagrama-de-componentes)
4. [Flujo de comunicación](#4-flujo-de-comunicación)
5. [Backend — capas y patrones](#5-backend--capas-y-patrones)
6. [Frontend — capas y patrones](#6-frontend--capas-y-patrones)
7. [Stack tecnológico](#7-stack-tecnológico)
8. [Modelo de seguridad](#8-modelo-de-seguridad)
9. [Modelo de despliegue](#9-modelo-de-despliegue)
10. [Decisiones arquitectónicas (ADRs)](#10-decisiones-arquitectónicas-adrs)
11. [Atributos de calidad](#11-atributos-de-calidad)
12. [Deuda técnica conocida](#12-deuda-técnica-conocida)
13. [Roadmap arquitectónico](#13-roadmap-arquitectónico)

---

## 1. Visión general

El sistema es una plataforma ERP liviana orientada a la gestión integral de inventario, ventas, compras, traslados, devoluciones, CRM y reacondicionamiento técnico de equipos. Está compuesto por dos aplicaciones desacopladas:

- **Backend (`admin-back`)** — API REST en Laravel 12 (PHP 8.2) que expone toda la lógica de negocio
- **Frontend (`admin-front`)** — Aplicación SPA en Vue 3 + Vuetify 3 que consume la API

La separación permite evolucionar cada parte de forma independiente, escalar solo el componente que lo requiera y eventualmente reemplazar el frontend (web → mobile) sin tocar la lógica de negocio.

---

## 2. Estilo arquitectónico

| Atributo | Decisión |
|---|---|
| Estilo | Cliente-Servidor con API stateless |
| Comunicación | HTTPS + JSON, autenticación con JWT Bearer |
| Estado de servidor | Stateless (sin sesión en cookie) — escalable horizontalmente |
| Autorización | RBAC granular con Spatie Permission (41 permisos atómicos) |
| Frontend | SPA (Single Page Application) con file-based routing |
| Persistencia | PostgreSQL relacional |
| Despliegue | Releases versionadas con symlink (blue-green) en VPS |

---

## 3. Diagrama de componentes

```
                    ┌──────────────────────────┐
                    │      Usuario final       │
                    │  (navegador HTTPS)       │
                    └────────────┬─────────────┘
                                 │
                                 ▼
              ┌──────────────────────────────────────┐
              │       Frontend SPA (Vue 3)           │
              │   admin-front/dist/  →  Nginx        │
              │                                      │
              │  • Vue Router (file-based)           │
              │  • Vuetify 3 UI                      │
              │  • Pinia (estado UI)                 │
              │  • $api (ofetch + JWT interceptor)   │
              │  • Guard de ruta por permiso         │
              └────────────────┬─────────────────────┘
                               │
                       Bearer JWT + JSON
                               │
                               ▼
              ┌──────────────────────────────────────┐
              │     Backend API (Laravel 12)         │
              │   PHP-FPM 8.2  +  Nginx              │
              │                                      │
              │  • routes/api.php                    │
              │  • Middleware: auth:api, permission  │
              │  • Controllers por dominio           │
              │  • FormRequest (validación)          │
              │  • Resources (serialización)         │
              │  • Services (2FA, Refurbish)         │
              │  • Policies / Gates                  │
              │  • Eloquent ORM                      │
              └─────┬─────────────────┬──────────────┘
                    │                 │
                    ▼                 ▼
        ┌────────────────────┐  ┌──────────────────┐
        │   PostgreSQL 14+   │  │  Redis (recom.)  │
        │                    │  │                  │
        │  • ~60 tablas      │  │  • Caché perm.   │
        │  • Soft deletes    │  │  • Sesiones      │
        │  • Auditoría       │  │  • Colas (jobs)  │
        └────────────────────┘  └──────────────────┘
                    │
                    ▼
        ┌────────────────────┐
        │   Storage local    │
        │   /storage/app     │
        │   • PDFs           │
        │   • Excel exports  │
        │   • Imágenes prod  │
        └────────────────────┘
```

---

## 4. Flujo de comunicación

### 4.1 Request autenticado

1. Usuario interactúa con la SPA → dispara una acción que requiere datos
2. SPA llama al endpoint vía `$api(...)` (ofetch wrapper)
3. El interceptor `onRequest` lee el JWT de `localStorage` y lo agrega como `Authorization: Bearer <token>`
4. Antes de enviar, valida el `exp` claim del token; si está expirado, hace logout y recarga
5. Backend recibe el request → middleware `auth:api` decodifica y valida el JWT
6. Si hay permisos requeridos, middleware `permission:<NAME>` verifica contra Spatie
7. Middleware `audit.route` registra el acceso (en módulos auditables)
8. Controller delega a Service o Repository (según módulo)
9. Service ejecuta lógica de negocio, posiblemente en transacción
10. Resource serializa la respuesta a JSON
11. Frontend recibe → actualiza vista (componentes Vuetify)

### 4.2 Login con 2FA

```
Frontend                                              Backend
   │                                                    │
   │  POST /api/auth/login {email, password}            │
   ├───────────────────────────────────────────────────>│
   │                                                    │ Valida credenciales
   │                                                    │ ¿Tiene 2FA?
   │                                                    │
   │  Caso A — sin 2FA:                                 │
   │  200 {access_token, user, permissions}             │
   │<───────────────────────────────────────────────────┤
   │                                                    │
   │  Caso B — con 2FA:                                 │
   │  200 {requires_2fa: true, mfa_token}               │
   │<───────────────────────────────────────────────────┤
   │                                                    │
   │  Redirige a /login-2fa                             │
   │  Usuario ingresa código TOTP                       │
   │                                                    │
   │  POST /api/auth/2fa/verify {mfa_token, code}       │
   ├───────────────────────────────────────────────────>│
   │  200 {access_token, user, permissions}             │
   │<───────────────────────────────────────────────────┤
```

---

## 5. Backend — capas y patrones

### 5.1 Capas

```
┌─────────────────────────────────────┐
│  Routes      (routes/api.php)       │  ← punto de entrada HTTP
├─────────────────────────────────────┤
│  Middleware  (auth, permission, …)  │  ← seguridad transversal
├─────────────────────────────────────┤
│  Controllers (HTTP coordination)    │  ← parsea entrada, devuelve respuesta
├─────────────────────────────────────┤
│  FormRequests (validación)          │  ← reglas de validación de payload
├─────────────────────────────────────┤
│  Services    (lógica de negocio)    │  ← reglas de dominio (parcial)
├─────────────────────────────────────┤
│  Models      (Eloquent ORM)         │  ← persistencia + relaciones
├─────────────────────────────────────┤
│  Resources   (serialización JSON)   │  ← transformadores de salida
├─────────────────────────────────────┤
│  Policies    (autorización fina)    │  ← chequeos por instancia
└─────────────────────────────────────┘
```

### 5.2 Patrones aplicados

| Patrón | Estado | Notas |
|---|---|---|
| Service Layer | Parcial | Implementado en `TwoFactorService`, `RefurbishService`. Otros módulos aún concentran lógica en controladores. |
| Resource Transformers | Sí | API Resources de Laravel para salida consistente |
| Policy-based access | Parcial | Policies definidas para entidades sensibles; el grueso de control va por `permission` middleware |
| Repository Pattern | No | Acceso directo vía Eloquent Models |
| Observer / Audit | Sí | Eventos de auditoría implícitos en modelos auditables |
| Soft Deletes | Sí | En todas las tablas operativas |

### 5.3 Estructura de carpetas (admin-back)

```
admin-back/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Por dominio (UserController, ProductController, …)
│   │   ├── Requests/         # FormRequest classes
│   │   ├── Resources/        # API Resources
│   │   └── Middleware/       # auth, permission, audit.route, throttle
│   ├── Models/               # Eloquent — 32 modelos
│   ├── Policies/             # Policies por entidad
│   └── Services/             # TwoFactorService, RefurbishService, …
├── config/
│   ├── auth.php
│   ├── jwt.php
│   ├── permission.php
│   ├── excel.php
│   └── …
├── database/
│   ├── migrations/           # ~60 migraciones
│   ├── seeders/              # PermissionsDemoSeeder, …
│   └── factories/            # UserFactory, SaleFactory
├── routes/
│   └── api.php               # Toda la API
├── bootstrap/
│   └── app.php               # Configuración de middleware y prefijo /api
├── storage/                  # Archivos generados (PDFs, exports, logs)
└── public/                   # index.php (entrada Nginx/Apache)
```

---

## 6. Frontend — capas y patrones

### 6.1 Capas

```
┌────────────────────────────────────────┐
│  Pages          (file-based routes)    │  ← una vista por archivo
├────────────────────────────────────────┤
│  Components     (UI reutilizable)      │  ← organizados por dominio
├────────────────────────────────────────┤
│  Stores Pinia   (estado UI global)     │  ← config de tema y layout
├────────────────────────────────────────┤
│  Composables    (lógica reutilizable)  │  ← useApi, etc.
├────────────────────────────────────────┤
│  Plugins        (router, vuetify, …)   │  ← bootstrap de la app
├────────────────────────────────────────┤
│  Utils          ($api, isPermission)   │  ← helpers transversales
└────────────────────────────────────────┘
```

### 6.2 File-based routing

`unplugin-vue-router` genera el router automáticamente desde `src/pages/`:

```
src/pages/
├── login.vue                     →  /login
├── dashboard/index.vue           →  /dashboard
├── product/list.vue              →  /product/list
├── product/edit/[id].vue         →  /product/edit/:id
├── refurbish/workbench-[id].vue  →  /refurbish/workbench-:id
└── [...error].vue                →  /:error+ (catch-all 404)
```

Cada `.vue` declara su permiso requerido vía `definePage({ meta: { permission: 'list_product' } })`.

### 6.3 Estructura de carpetas (admin-front)

```
admin-front/src/
├── @core/                  # Sistema base (theme, helpers, componentes core)
├── @layouts/               # Layouts (vertical nav, blank, etc.)
├── pages/                  # Vistas → rutas auto-generadas
│   ├── dashboard/
│   ├── product/
│   ├── sales/
│   ├── purchase/
│   ├── transport/
│   ├── refurbish/
│   ├── crm/
│   ├── configuration/
│   ├── audit/
│   └── …
├── components/             # Componentes por dominio
│   └── inventory/
│       ├── product/
│       ├── client/
│       ├── sale/
│       └── …
├── composables/            # useApi.js
├── utils/                  # api.js (HTTP), constants.js (permisos)
├── plugins/
│   ├── 1.router/           # Router + guards
│   ├── 2.pinia.js          # Pinia
│   ├── vuetify/            # Tema y configuración Vuetify
│   └── iconify/            # Bundle de iconos
├── layouts/                # Layouts de página
└── navigation/             # Menú vertical (filtrado por permisos)
```

### 6.4 Manejo de estado

| Tipo de estado | Mecanismo |
|---|---|
| Tema y layout (UI) | Pinia `useConfigStore`, `useLayoutConfigStore` |
| Token y datos del usuario | `localStorage` (`token`, `user`) |
| Estado de formularios | Local de componente (`ref`, `reactive`) |
| Datos de listados | Local + paginación con llamadas directas |

> **Nota arquitectónica**: el estado de negocio NO está centralizado en Pinia. Cada vista hace sus propias llamadas a `$api`. Esto simplifica el mental model pero impide compartir estado entre vistas (ej. lista de clientes en cache). Documentado como deuda técnica en §12.

---

## 7. Stack tecnológico

### 7.1 Backend

| Componente | Versión | Propósito |
|---|---|---|
| PHP | 8.2+ | Runtime |
| Laravel | 12.x | Framework HTTP + ORM + tooling |
| PostgreSQL | 14+ | Base de datos |
| `php-open-source-saver/jwt-auth` | 2.8+ | JWT |
| `spatie/laravel-permission` | 6.16+ | RBAC |
| `pragmarx/google2fa` | * | TOTP |
| `bacon/bacon-qr-code` | * | QR para 2FA |
| `maatwebsite/excel` | 3.1+ | Import/export Excel |
| `barryvdh/laravel-dompdf` | 3.1+ | Generación de PDF |
| Composer | 2.x | Gestor de dependencias |
| PHPUnit | 11.5+ | Testing |
| Laravel Pint | 1.13+ | Code style |

### 7.2 Frontend

| Componente | Versión | Propósito |
|---|---|---|
| Vue | 3.4.25 | Framework |
| Vuetify | 3.5.15 | UI Components (Material Design 3) |
| Vue Router | 4.3.2 | Ruteo |
| Pinia | 2.1.7 | Estado |
| Vite | 5.2.10 | Bundler / Dev server |
| `unplugin-vue-router` | 0.8.6 | File-based routing |
| ofetch | 1.3.4 | HTTP client |
| `@vueuse/core` | 10.9.0 | Composables reactivos |
| ApexCharts + Chart.js | — | Gráficos |
| TipTap | 2.3.0 | Editor WYSIWYG |
| MapboxGL | 3.2.0 | Mapas |
| `@casl/ability` | 6.7.1 | (Disponible, no usado activamente) |
| Playwright | 1.59.1 | Testing E2E |
| MSW | 2.2.14 | Mock API en dev |
| pnpm | 9.0.6 | Gestor de paquetes |
| ESLint + Stylelint | — | Linting |

### 7.3 DevOps

| Componente | Propósito |
|---|---|
| GitHub Actions | CI/CD — push a `main` dispara build + tests + deploy SSH al VPS. Ver [docs/CICD.md](./CICD.md) |
| `deploy.sh` | Script de despliegue blue-green con releases |
| Nginx | Reverse proxy + servidor estático del SPA |
| PHP-FPM 8.2 | Procesador PHP |
| Docker (frontend) | Imagen multi-stage para SPA |
| systemd | Gestión de procesos (queue worker) |

---

## 8. Modelo de seguridad

### 8.1 Capas de seguridad

```
┌──────────────────────────────────────────┐
│  HTTPS (TLS 1.3)                         │  ← cifrado en tránsito
├──────────────────────────────────────────┤
│  Rate limiting (login, MFA, API)         │  ← anti-bruteforce
├──────────────────────────────────────────┤
│  JWT (HS256, TTL 8h, refresh 14d)        │  ← autenticación stateless
├──────────────────────────────────────────┤
│  2FA TOTP + recovery codes               │  ← segundo factor opcional
├──────────────────────────────────────────┤
│  RBAC (41 permisos × N roles)            │  ← autorización granular
├──────────────────────────────────────────┤
│  Policies por entidad                    │  ← chequeos finos por instancia |
├──────────────────────────────────────────┤
│  Auditoría (eventos + diff de campos)    │  ← trazabilidad completa
├──────────────────────────────────────────┤
│  bcrypt (12 rounds) sobre contraseñas    │  ← hashing en reposo
├──────────────────────────────────────────┤
│  Secrets cifrados (2FA secrets)          │  ← cifrado de datos sensibles
└──────────────────────────────────────────┘
```

### 8.2 Detalles

Ver el documento dedicado: [docs/SEGURIDAD.md](./SEGURIDAD.md).

---

## 9. Modelo de despliegue

### 9.1 Topología sugerida

```
                 Internet
                    │
                    ▼
            ┌──────────────┐
            │   Nginx LB   │  (opcional, si hay alta disp.)
            └──────┬───────┘
                   │
         ┌─────────┼─────────┐
         ▼                   ▼
   ┌──────────┐         ┌──────────┐
   │ Nginx +  │         │ Nginx +  │
   │ PHP-FPM  │         │ PHP-FPM  │
   │ (back)   │         │ (back)   │
   └────┬─────┘         └────┬─────┘
        │                    │
        └─────────┬──────────┘
                  ▼
           ┌────────────┐
           │ PostgreSQL │
           │   (RDS o   │
           │   self-host)│
           └────────────┘
                  │
           ┌────────────┐
           │   Redis    │  (caché, sesiones, colas)
           └────────────┘

Frontend:
   Build estático servido por Nginx
   (puede ir en CDN: Cloudflare, S3+CloudFront)
```

### 9.2 Despliegue actual (single-node)

```
VPS Linux
├── /var/www/sistema_inventario/
│   ├── current → releases/<timestamp>/    (symlink)
│   ├── releases/                          (últimas 3 conservadas)
│   └── shared/
│       ├── .env                           (persistente entre releases)
│       └── storage/                       (PDFs, exports, logs)
├── PostgreSQL 14
├── Redis (recomendado)
├── Nginx (back + front)
└── PHP-FPM 8.2
```

Ver flujo detallado en [docs/INSTALACION.md §6](./INSTALACION.md#6-despliegue-en-producción-deploysh).

---

## 10. Decisiones arquitectónicas (ADRs)

### ADR-001: API stateless con JWT

**Contexto**: el frontend SPA debe poder autenticarse sin depender de cookies de sesión, y el backend debe poder escalar horizontalmente.

**Decisión**: usar JWT con guard `api` y deshabilitar la sesión web para rutas `/api/*`.

**Consecuencias**:
- ✅ Escalabilidad horizontal sin sticky sessions
- ✅ Mismo backend puede servir mobile, partners, etc.
- ⚠️ Token en `localStorage` es vulnerable a XSS (mitigado con CSP estricta — ver SEGURIDAD.md)
- ⚠️ Logout requiere blacklist activa (implementado)

### ADR-002: RBAC granular con Spatie Permission

**Contexto**: los permisos deben ser verificables tanto en backend como en frontend con un único catálogo.

**Decisión**: Spatie Permission como única fuente de verdad. El backend devuelve la lista de permisos del usuario en el login, el frontend la guarda y la usa en `isPermission()`.

**Consecuencias**:
- ✅ Doble validación (frontend oculta UI + backend bloquea acceso)
- ✅ Cambios de permisos visibles inmediatamente al re-loguear
- ⚠️ Si se agrega un permiso nuevo, hay que registrarlo en el seeder y en la UI (constants.js)

### ADR-003: File-based routing en frontend

**Contexto**: el equipo debe poder crear nuevas vistas sin modificar configuración central.

**Decisión**: usar `unplugin-vue-router` con convención por archivo en `src/pages/`.

**Consecuencias**:
- ✅ Nuevas rutas requieren solo crear el `.vue`
- ✅ Tipos de ruta autogenerados (`typed-router.d.ts`)
- ⚠️ Convenciones de nombre deben respetarse (kebab-case)

### ADR-004: PostgreSQL como motor

**Contexto**: el modelo tiene fuerte componente relacional (productos, almacenes, ventas, traslados con muchas FKs) y requiere transacciones ACID.

**Decisión**: PostgreSQL 14+. Soporte nativo de tipos avanzados (`inet`, `jsonb`), particionamiento, vistas materializadas si se requieren reportes pesados.

**Consecuencias**:
- ✅ Integridad referencial fuerte
- ✅ Soporte excelente de ORM Eloquent
- ⚠️ Algunos hostings económicos no traen PostgreSQL pre-instalado

### ADR-005: Despliegue blue-green con releases versionados

**Contexto**: minimizar tiempo de inactividad y permitir rollback inmediato.

**Decisión**: `deploy.sh` mantiene N releases en `releases/`, con `current` como symlink a la activa. Para rollback: cambiar el symlink y reiniciar PHP-FPM.

**Consecuencias**:
- ✅ Rollback inmediato (segundos)
- ✅ Almacenamiento compartido (`storage/`, `.env`) preservado
- ⚠️ Migraciones de BD no son automáticamente reversibles — se requiere proceso manual para rollback con cambios de schema

---

## 11. Atributos de calidad

| Atributo | Estado actual | Comentario |
|---|---|---|
| **Disponibilidad** | Nivel medio | Single-node. Para 99.9% requiere LB + replica de BD |
| **Escalabilidad** | Vertical alta, horizontal media | Backend stateless permite scale-out, pero falta caché distribuida |
| **Seguridad** | Alta | JWT + 2FA + RBAC + auditoría. Pendiente migrar a cookies HttpOnly |
| **Mantenibilidad** | Media-alta | Separación clara de capas; deuda técnica en naming legacy |
| **Performance** | Media | Falta índices en algunos joins frecuentes; N+1 posible en listados grandes |
| **Observabilidad** | Baja-media | Logs locales sin agregación. Sin APM ni alerting activo |
| **Recuperabilidad** | Media | Backup manual. Pendiente automatización (ver OPERACIONES.md) |
| **Trazabilidad** | Alta | Auditoría completa de eventos y cambios |

---

## 12. Deuda técnica conocida

### 12.1 Naming legacy

Tablas y columnas con typos no corregidos para no romper compatibilidad:
- `puchases`, `puchase_details` (debería `purchases`)
- `immporte`, `impote` (debería `importe`)
- `warehause_*_id` (debería `warehouse_*_id`)
- `sucuarsal_id` (debería `sucursal_id`)
- `state_mayment` (debería `state_payment`)
- `resoslution_*` (debería `resolution_*`)

**Estrategia recomendada**: migración por fases con doble lectura/escritura — no hacerlo en producción sin plan.

### 12.2 Lógica en controladores

Varios controladores transaccionales contienen lógica de negocio (cálculo de totales, descuento de stock, validación de estados). Idealmente debería migrarse a Services.

### 12.3 Ausencia de tests de negocio

PHPUnit y Playwright están instalados, pero la cobertura es escasa. Tests críticos pendientes:
- Cálculo de IVA y descuentos en ventas
- Descuento de stock en venta y traslado
- Cálculo de costo refurbished
- Backfill de permisos heredados

### 12.4 Estado en localStorage del frontend

`token` y `user` en localStorage exponen datos a ataques XSS. Migración a cookies `HttpOnly` requiere refactor en backend (CSRF para mutaciones) y frontend.

### 12.5 Sin versionado de API

La API actual está en `/api` directamente. Para evolucionar sin romper clientes existentes se recomienda introducir `/api/v1` y mantener compatibilidad.

### 12.6 Bugs puntuales documentados

- `ConversionController::index` usa `oderBy` (typo)
- `ConversionController::store` accede a `auth('api')->user->id` (debería ser `user()->id`)
- `TransportDetailController::attentionDelivery` puede usar variable nula
- Ruta `POST /refurbish/start/{id}` declarada sin método correspondiente
- `transport/edit/[id].vue` usa variable `index` no definida al eliminar detalle

> Estos bugs están documentados en `INFORME_SEGURIDAD_ROBUSTEZ.md` y deben atenderse en el siguiente sprint.

---

## 13. Roadmap arquitectónico

### Corto plazo (0-4 semanas)
- Corregir bugs puntuales (`oderBy`, accesos a `user->id`, métodos faltantes)
- Estandarizar respuestas de error y códigos HTTP
- Endurecer validaciones en endpoints transaccionales
- Asegurar logout llama `/auth/logout` siempre
- Limpiar páginas residuales del template (`index.vue` raíz, `products.vue`)

### Mediano plazo (1-3 meses)
- Extraer lógica transaccional de controladores a Services
- Suite de tests automatizados (Feature + E2E) para flujos críticos
- Versionar API (`/api/v1`) con compat layer para clientes actuales
- Centralizar caché y sesiones en Redis
- Implementar comando `audit:archive` para retention de logs

### Largo plazo (3-12 meses)
- Migrar token a cookies HttpOnly + CSRF
- Reemplazar campos numéricos de estado por enums tipados (PostgreSQL `ENUM`)
- Reportes pesados delegados a colas
- Stack de observabilidad (Sentry/Datadog + ELK)
- Réplica de lectura en PostgreSQL para reportes
- CDN para activos estáticos del front

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Configuración](./CONFIGURACION.md)
- [Modelo de base de datos](./MODELO_BD.md)
- [API REST](./API.md)
- [Seguridad y hardening](./SEGURIDAD.md)
- [Operaciones y runbook](./OPERACIONES.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

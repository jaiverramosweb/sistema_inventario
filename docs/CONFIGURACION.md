# Manual de Configuración

> **Sistema de Inventario Pro** — Guía de configuración del sistema, variables de entorno, autenticación, permisos y parámetros operativos.

---

## Tabla de contenidos

1. [Variables de entorno — Backend](#1-variables-de-entorno--backend)
2. [Variables de entorno — Frontend](#2-variables-de-entorno--frontend)
3. [Autenticación JWT](#3-autenticación-jwt)
4. [Autenticación de dos factores (2FA)](#4-autenticación-de-dos-factores-2fa)
5. [Roles y permisos (Spatie Permission)](#5-roles-y-permisos-spatie-permission)
6. [Cuentas iniciales](#6-cuentas-iniciales)
7. [Auditoría](#7-auditoría)
8. [Importación/exportación (Excel y PDF)](#8-importaciónexportación-excel-y-pdf)
9. [Rate limiting (throttle)](#9-rate-limiting-throttle)
10. [Caché, colas y sesiones](#10-caché-colas-y-sesiones)
11. [Configuración de timezone y locale](#11-configuración-de-timezone-y-locale)
12. [Checklist de configuración para producción](#12-checklist-de-configuración-para-producción)

---

## 1. Variables de entorno — Backend

Archivo: `admin-back/.env`

### 1.1 Aplicación

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `APP_NAME` | string | `Laravel` | Nombre que aparece en logs y emails |
| `APP_ENV` | enum | `production` | `local` \| `development` \| `staging` \| `production` |
| `APP_KEY` | string | — | Clave de cifrado (generar con `php artisan key:generate`) |
| `APP_DEBUG` | bool | `false` | **DEBE estar en `false` en producción** (expone stack traces) |
| `APP_URL` | url | `http://localhost` | URL pública del backend |
| `APP_TIMEZONE` | string | `UTC` | Zona horaria. Para Colombia: `America/Bogota` |
| `APP_LOCALE` | string | `en` | Idioma. Recomendado: `es` |
| `APP_FALLBACK_LOCALE` | string | `en` | Idioma de respaldo |

### 1.2 Base de datos (PostgreSQL)

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `DB_CONNECTION` | enum | `pgsql` | Motor de BD. Opciones: `pgsql` \| `mysql` \| `sqlite` |
| `DB_HOST` | string | `127.0.0.1` | Host de la BD |
| `DB_PORT` | int | `5432` | Puerto. PostgreSQL: 5432 \| MySQL: 3306 |
| `DB_DATABASE` | string | — | Nombre de la BD (ej. `sistema_inventario`) |
| `DB_USERNAME` | string | — | Usuario con permisos sobre la BD |
| `DB_PASSWORD` | string | — | Contraseña del usuario |

### 1.3 JWT

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `JWT_SECRET` | string | — | Clave HMAC. Generar con `php artisan jwt:secret` |
| `JWT_ALGO` | enum | `HS256` | Algoritmo. `HS256` (default), `RS256` requiere claves pública/privada |
| `JWT_TTL` | int (min) | `480` | Tiempo de vida del access token (8 horas) |
| `JWT_REFRESH_TTL` | int (min) | `20160` | Ventana de refresh (14 días) |
| `JWT_BLACKLIST_ENABLED` | bool | `true` | Permite invalidar tokens en logout |
| `JWT_BLACKLIST_GRACE_PERIOD` | int (s) | `0` | Margen para tokens recién emitidos durante refresh |
| `JWT_LOCK_SUBJECT` | bool | `true` | Previene suplantación entre modelos |

### 1.4 Logs

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `LOG_CHANNEL` | enum | `stack` | `stack` \| `single` \| `daily` \| `slack` \| `papertrail` |
| `LOG_LEVEL` | enum | `error` | `debug` \| `info` \| `warning` \| `error` \| `critical` |
| `LOG_DEPRECATIONS_CHANNEL` | string | `null` | Canal para advertencias de depreciación |

> **Producción**: usar `LOG_CHANNEL=daily` con `LOG_LEVEL=error` para rotación automática y bajo ruido.

### 1.5 Caché, sesión, colas

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `CACHE_STORE` | enum | `database` | `redis` \| `memcached` \| `database` \| `file` |
| `CACHE_PREFIX` | string | — | Prefijo de claves (recomendado por entorno) |
| `SESSION_DRIVER` | enum | `database` | `database` \| `redis` \| `file` \| `cookie` |
| `SESSION_LIFETIME` | int (min) | `120` | TTL de sesión |
| `QUEUE_CONNECTION` | enum | `database` | `redis` \| `database` \| `sync` \| `sqs` |

> **Producción recomendada**: Redis para caché, sesiones y colas. Reduce I/O en BD y mejora latencia.

### 1.6 Mail

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `MAIL_MAILER` | enum | `smtp` | `smtp` \| `ses` \| `mailgun` \| `sendmail` \| `log` |
| `MAIL_HOST` | string | — | Servidor SMTP |
| `MAIL_PORT` | int | `587` | Puerto SMTP |
| `MAIL_USERNAME` | string | — | Usuario SMTP |
| `MAIL_PASSWORD` | string | — | Contraseña SMTP |
| `MAIL_ENCRYPTION` | enum | `tls` | `tls` \| `ssl` \| `null` |
| `MAIL_FROM_ADDRESS` | email | — | Remitente por defecto |
| `MAIL_FROM_NAME` | string | `${APP_NAME}` | Nombre del remitente |

### 1.7 Otros

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `BCRYPT_ROUNDS` | int | `12` | Iteraciones de bcrypt para hashing de contraseñas |
| `FILESYSTEM_DISK` | enum | `local` | Disco por defecto: `local` \| `public` \| `s3` |

### 1.8 Plantilla `.env` mínima

```env
APP_NAME="Sistema de Inventario"
APP_ENV=production
APP_KEY=base64:GENERAR_CON_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://api.sistema-inventario.com
APP_TIMEZONE=America/Bogota
APP_LOCALE=es

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sistema_inventario
DB_USERNAME=inventario_user
DB_PASSWORD=CHANGE_ME

JWT_SECRET=GENERAR_CON_php_artisan_jwt:secret
JWT_TTL=480
JWT_REFRESH_TTL=20160
JWT_BLACKLIST_ENABLED=true

LOG_CHANNEL=daily
LOG_LEVEL=error

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.empresa.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@sistema-inventario.com
MAIL_PASSWORD=CHANGE_ME
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@sistema-inventario.com
MAIL_FROM_NAME="Sistema de Inventario"

FILESYSTEM_DISK=local
BCRYPT_ROUNDS=12
```

---

## 2. Variables de entorno — Frontend

Archivo: `admin-front/.env`

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `VITE_API_BASE_URL` | url | `/api` | URL base del backend. En dev: `http://127.0.0.1:8000/api`. En prod: `https://api.sistema-inventario.com/api` |

### Para tests E2E (Playwright)

| Variable | Tipo | Default | Descripción |
|---|---|---|---|
| `PLAYWRIGHT_BASE_URL` | url | `http://localhost:5173` | URL del front a testear |
| `PW_E2E_EMAIL` | email | `superadmin@sitecsas.com` | Usuario para login en tests |
| `PW_E2E_PASSWORD` | string | — | Contraseña del usuario (REQUERIDA para correr tests) |

> **Convención Vite**: solo las variables con prefijo `VITE_` se exponen al cliente. Cualquier secreto NUNCA debe ir prefijado con `VITE_` porque se incluye en el bundle público.

---

## 3. Autenticación JWT

### 3.1 Flujo de autenticación

```
┌──────────┐                                      ┌──────────┐
│ Frontend │                                      │ Backend  │
└────┬─────┘                                      └────┬─────┘
     │                                                 │
     │  POST /api/auth/login {email, password}         │
     ├────────────────────────────────────────────────>│
     │                                                 │ ─ Valida credenciales
     │                                                 │ ─ Si tiene 2FA → respuesta requires_2fa
     │                                                 │ ─ Si no, emite JWT
     │  200 { access_token, user, permissions }        │
     │<────────────────────────────────────────────────┤
     │                                                 │
     │  GET /api/* + Authorization: Bearer <token>     │
     ├────────────────────────────────────────────────>│
     │                                                 │ ─ Middleware auth:api valida JWT
     │                                                 │ ─ Middleware permission:X valida permiso
     │  200 { data }                                   │
     │<────────────────────────────────────────────────┤
     │                                                 │
     │  POST /api/auth/refresh                         │
     ├────────────────────────────────────────────────>│
     │  200 { access_token nuevo }                     │
     │<────────────────────────────────────────────────┤
     │                                                 │
     │  POST /api/auth/logout                          │
     ├────────────────────────────────────────────────>│ ─ Token agregado a blacklist
     │  200 { message }                                │
     │<────────────────────────────────────────────────┤
```

### 3.2 Configuración de TTL

`JWT_TTL=480` da 8 horas de vida al access token, lo cual cubre una jornada laboral completa sin pedir login. Si la jornada es más corta o la sensibilidad lo exige, reducirlo a 60 o 120 minutos y depender del `refresh`.

`JWT_REFRESH_TTL=20160` (14 días) significa que el frontend puede pedir un token nuevo dentro de esa ventana sin reautenticación.

### 3.3 Almacenamiento del token en el frontend

El frontend guarda el token en `localStorage`:

```javascript
localStorage.setItem('token', '<JWT>')
localStorage.setItem('user', JSON.stringify({ id, name, email, role, permissions }))
```

`src/utils/api.js` lee `localStorage.token`, valida su `exp` claim antes de cada request, y si está expirado limpia el storage y recarga la página (logout forzado).

> **Trade-off de seguridad**: `localStorage` es vulnerable a XSS. La política de seguridad del proyecto está documentada en [docs/SEGURIDAD.md](./SEGURIDAD.md). Si se requiere endurecer, migrar a cookies `HttpOnly` + `SameSite=Strict` requiere cambios en back y front.

---

## 4. Autenticación de dos factores (2FA)

El sistema soporta TOTP (Google Authenticator, Authy, 1Password) usando `pragmarx/google2fa`.

### 4.1 Activación por usuario

1. Usuario navega a su perfil (`/perfil`)
2. Inicia configuración: `POST /api/auth/2fa/setup/init` → devuelve QR + secret
3. Usuario escanea el QR con su app autenticadora
4. Usuario ingresa código de 6 dígitos: `POST /api/auth/2fa/setup/verify`
5. Sistema confirma, marca `two_factor_enabled = true` y emite **códigos de recuperación** (single-use)

### 4.2 Login con 2FA

1. `POST /api/auth/login` con email/password
2. Si el usuario tiene 2FA: respuesta `{ requires_2fa: true, challenge_id }`
3. Frontend redirige a `/login-2fa`
4. Usuario ingresa código TOTP: `POST /api/auth/2fa/verify { challenge_id, code }`
5. Si correcto → emite JWT
6. Si pierde acceso al dispositivo: usar código de recuperación con `POST /api/auth/2fa/recovery`

### 4.3 Recuperación

- 8 códigos de un solo uso generados al activar 2FA
- Almacenados hasheados en `user_recovery_codes`
- Usar `POST /api/auth/2fa/recovery/regenerate` para generar nuevos (invalida los anteriores)

### 4.4 Desactivación

`POST /api/auth/2fa/disable` con confirmación de contraseña actual.

> **Política recomendada**: hacer 2FA obligatorio para roles con permisos sensibles (Super-Admin, Gerentes, Auditores).

---

## 5. Roles y permisos (Spatie Permission)

### 5.1 Modelo

- **Roles**: agrupaciones lógicas (Super-Admin, Vendedor, Bodeguero, Gerente, etc.)
- **Permisos**: acciones atómicas (`list_product`, `register_sale`, etc.)
- Un usuario tiene **un rol** (asignado en el campo `users.role_id` y replicado en `model_has_roles` de Spatie)
- Un rol tiene **N permisos** vía la tabla `role_has_permissions`

### 5.2 Catálogo de permisos (41)

| Módulo | Permisos |
|---|---|
| Dashboard | `dashboard` |
| Configuración | `settings` |
| Roles | `register_role`, `list_role`, `edit_role`, `delete_role` |
| Usuarios | `register_user`, `list_user`, `edit_user`, `delete_user` |
| Productos | `register_product`, `list_product`, `edit_product`, `delete_product`, `show_inventory_product`, `show_wallet_price_product` |
| Clientes | `register_client`, `list_client`, `edit_client`, `delete_client` |
| Ventas | `register_sale`, `list_sale`, `edit_sale`, `delete_sale` |
| Compras | `register_purchase`, `list_purchase`, `edit_purchase`, `delete_purchase` |
| Traslados | `register_transport`, `list_transport`, `edit_transport`, `delete_transport` |
| Reacondicionamiento | `register_refurbish`, `list_refurbish`, `edit_refurbish`, `delete_refurbish` |
| CRM Leads | `register_lead`, `list_lead`, `edit_lead`, `delete_lead`, `convert_lead` |
| CRM Oportunidades | `register_opportunity`, `list_opportunity`, `edit_opportunity`, `delete_opportunity` |
| CRM Actividades | `register_crm_activity`, `list_crm_activity`, `edit_crm_activity`, `delete_crm_activity` |
| Operaciones | `kardex`, `conversions`, `return` |
| Auditoría | `view_audit_logs`, `export_audit_logs` |

### 5.3 Reglas de backfill (heredadas)

El seeder `PermissionsDemoSeeder` aplica reglas de coherencia automática al asignar permisos a un rol:

- `list_client` ⇒ `list_lead`
- `register_client` ⇒ `register_lead`, `convert_lead`
- `list_sale` ⇒ `list_opportunity`, `list_crm_activity`
- `list_product` ⇒ `list_refurbish`

> Esto garantiza que un Vendedor que puede ver clientes también pueda ver leads (que son prospectos del mismo flujo), sin requerir asignación manual.

### 5.4 Crear un rol nuevo

```bash
php artisan tinker
>>> use Spatie\Permission\Models\Role;
>>> $role = Role::create(['name' => 'Bodeguero', 'guard_name' => 'api']);
>>> $role->givePermissionTo([
        'list_product','show_inventory_product',
        'list_purchase','list_transport','register_transport','edit_transport',
        'kardex'
    ]);
```

Luego, asignar el rol a un usuario:

```bash
>>> $user = App\Models\User::find(5);
>>> $user->syncRoles(['Bodeguero']);
```

### 5.5 Validación en runtime

**Backend** — middleware `permission:<NAME>` se aplica a las rutas API:

```php
Route::post('products', [ProductController::class, 'store'])
    ->middleware('permission:register_product');
```

**Frontend** — función `isPermission()` controla la UI:

```vue
<VBtn v-if="isPermission('register_product')" @click="openAddDialog">
  Agregar producto
</VBtn>
```

Y el guard de router (`src/plugins/1.router/guards.js`) bloquea el acceso a rutas según `meta.permission`.

---

## 6. Cuentas iniciales

### 6.1 Super-Admin demo (creado por el seeder)

| Campo | Valor |
|---|---|
| Email | `superadmin@sitecsas.com` |
| Rol | `Super-Admin` (todos los permisos) |
| Sucursal | Colombia |

> **Producción**: cambiar la contraseña inmediatamente después de la primera sesión, o editar el seeder antes de ejecutarlo.

### 6.2 Política de contraseñas recomendada

| Aspecto | Valor recomendado |
|---|---|
| Longitud mínima | 12 caracteres |
| Complejidad | Mayúsculas + minúsculas + números + símbolos |
| Rotación | Cada 90 días para roles administrativos |
| Reutilización | Bloquear las últimas 5 contraseñas |
| Hashing | bcrypt con `BCRYPT_ROUNDS=12` (default Laravel) |

> Estas políticas no están aplicadas automáticamente por el sistema actual. Para implementarlas se recomienda agregar un middleware de validación de contraseña al endpoint de cambio de contraseña.

---

## 7. Auditoría

### 7.1 Qué se audita

- **Acceso a rutas protegidas** (vía middleware `audit.route`): usuario, IP, ruta, timestamp
- **Cambios sobre modelos auditables**: User, Product, Sale, Purchase, Transport, Refurbish — incluye campo modificado, valor anterior y nuevo
- **Navegación del frontend**: cada cambio de ruta dispara `POST /api/audit/navigation` (con throttle de 45s para evitar ruido)

### 7.2 Tablas

- `audit_events` — registro principal (qué, quién, cuándo, dónde)
- `audit_event_changes` — diff de campos (uno por campo modificado)
- `audit_exports` — registro de exportaciones de logs (XLSX/CSV)

### 7.3 Acceso a logs

- Vista UI: `/audit/logs` (requiere permiso `view_audit_logs`)
- Exportación: `/audit/logs/export?format=xlsx|csv` (requiere `export_audit_logs`)

### 7.4 Retención

Por defecto los registros NO se eliminan. Se recomienda:

1. Conservar logs en línea por 12 meses
2. Archivar en almacenamiento frío (S3, disco) por 5-7 años
3. Implementar comando Artisan programado para mover/comprimir registros antiguos

Ejemplo (a implementar):

```bash
php artisan audit:archive --older-than=365
```

---

## 8. Importación/exportación (Excel y PDF)

### 8.1 Excel (Maatwebsite/Excel)

| Operación | Endpoint | Permiso |
|---|---|---|
| Importar productos | `POST /api/products/import-excel` | `register_product` |
| Exportar productos | `GET /api/products-excel` | `list_product` |
| Exportar ventas | `GET /api/sales-excel` | `list_sale` |
| Exportar logs auditoría | `GET /api/audit/logs/export?format=xlsx` | `export_audit_logs` |

Configuración: `config/excel.php`

| Parámetro | Default | Descripción |
|---|---|---|
| `chunk_size` | 1000 | Filas por chunk al importar |
| `pdf` | `DOMPDF` | Driver de PDF cuando se exporta como PDF |

> **Plantilla de importación**: `ejemplo_import.xlsx` en la raíz del repositorio.

### 8.2 PDF (Barryvdh/DomPDF)

| Documento | Endpoint |
|---|---|
| Comprobante de venta | `GET /api/sales-pdf/{id}` |
| Comprobante de compra | `GET /api/pushases-pdf/{id}` |
| Guía de traslado | `GET /api/transport-pdf/{id}` |

---

## 9. Rate limiting (throttle)

Limitadores definidos para prevenir abuso:

| Throttle | Límite | Aplicado a |
|---|---|---|
| `throttle:login` | 5 intentos / 1 min | `POST /api/auth/login`, `/register` |
| `throttle:mfa_challenge` | 10 intentos / 5 min | Verificación 2FA y recovery codes |
| `throttle:mfa_settings` | 30 cambios / hora | Endpoints de configuración 2FA y perfil |
| `throttle:api` (default) | 60 req / min | Resto de endpoints autenticados |

> Si un usuario excede el límite recibe HTTP 429. El frontend muestra mensaje "Demasiados intentos, intentá en X minutos".

---

## 10. Caché, colas y sesiones

### 10.1 Caché

`CACHE_STORE=redis` recomendado en producción. Spatie Permission usa caché agresivamente: por defecto 24 horas. Si se modifica un permiso o rol manualmente desde la BD, ejecutar:

```bash
php artisan permission:cache-reset
```

### 10.2 Colas

`QUEUE_CONNECTION=redis` permite encolar tareas pesadas (importaciones, exportaciones grandes, generación de PDFs en lote).

Worker en producción (systemd):

```ini
# /etc/systemd/system/inventario-queue.service
[Unit]
Description=Inventario Queue Worker
After=network.target

[Service]
User=Sitecsas
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/sistema_inventario/current/admin-back/artisan queue:work --queue=default --tries=3 --timeout=300

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable inventario-queue
sudo systemctl start inventario-queue
```

### 10.3 Sesiones

El backend es API stateless (JWT). Sin embargo, Laravel mantiene sesiones para algunos componentes internos (csrf en rutas web). Configurar `SESSION_DRIVER=database` o `redis` en producción multi-nodo.

---

## 11. Configuración de timezone y locale

```env
APP_TIMEZONE=America/Bogota
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
```

Esto asegura:
- Fechas en logs y BD en hora local
- Mensajes de validación en español
- Formato de fechas/números acorde a Colombia

> El frontend usa `vue-i18n` para traducción. Las claves de traducción están en `admin-front/src/plugins/i18n/locales/`.

---

## 12. Checklist de configuración para producción

- [ ] `APP_ENV=production` y `APP_DEBUG=false`
- [ ] `APP_KEY` generado y único por entorno
- [ ] `JWT_SECRET` generado y único por entorno
- [ ] Base de datos PostgreSQL con usuario dedicado y backups configurados
- [ ] HTTPS forzado en Nginx con certificado válido (Let's Encrypt o comercial)
- [ ] `LOG_LEVEL=error` y `LOG_CHANNEL=daily`
- [ ] Redis configurado para caché, sesiones y colas (o database como fallback)
- [ ] SMTP real configurado para notificaciones
- [ ] Worker de colas activo (systemd) si hay tareas asíncronas
- [ ] Cron de Laravel registrado: `* * * * * cd /var/www/sistema_inventario/current/admin-back && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Permisos de `storage/` y `bootstrap/cache/` configurados (775, owner web)
- [ ] `php artisan config:cache && route:cache && view:cache && event:cache` ejecutado
- [ ] Contraseña del Super-Admin demo cambiada
- [ ] 2FA habilitado para usuarios administrativos
- [ ] CORS configurado en `config/cors.php` solo para el dominio del frontend
- [ ] Rate limiting probado con la carga esperada
- [ ] Backup automático de BD configurado (ver [docs/OPERACIONES.md](./OPERACIONES.md))
- [ ] Monitoreo activo (uptime, errores, disco) — ver [docs/OPERACIONES.md](./OPERACIONES.md)

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Modelo de base de datos](./MODELO_BD.md)
- [Hardening y seguridad](./SEGURIDAD.md)
- [Manual de operaciones](./OPERACIONES.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

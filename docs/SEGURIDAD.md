# Seguridad y Hardening

> **Sistema de Inventario Pro** — Controles aplicados, hallazgos auditados y guía de hardening.

---

## Tabla de contenidos

1. [Modelo de seguridad](#1-modelo-de-seguridad)
2. [Controles implementados](#2-controles-implementados)
3. [Política de contraseñas](#3-política-de-contraseñas)
4. [Política 2FA](#4-política-2fa)
5. [Auditoría](#5-auditoría)
6. [Hallazgos y plan de remediación](#6-hallazgos-y-plan-de-remediación)
7. [Checklist de hardening para producción](#7-checklist-de-hardening-para-producción)
8. [Respuesta a incidentes](#8-respuesta-a-incidentes)

---

## 1. Modelo de seguridad

| Capa | Mecanismo |
|---|---|
| Transporte | HTTPS (TLS 1.3) obligatorio en producción |
| Autenticación | JWT (HS256, TTL 8h, refresh 14d) |
| Segundo factor | TOTP (Google2FA) + 8 códigos de recuperación single-use |
| Autorización | RBAC granular con Spatie Permission (41 permisos) |
| Validación | FormRequests (parcial — ver hallazgos) |
| Anti-bruteforce | Throttling: `login` (5/min), `mfa_challenge` (10/5min), `mfa_settings` (30/h) |
| Hashing contraseñas | bcrypt 12 rounds |
| Cifrado en reposo | 2FA secrets cifrados (AES via Laravel Crypt) |
| Auditoría | Eventos + diff de campos en `audit_events` y `audit_event_changes` |
| Soft deletes | Permite revertir eliminaciones accidentales |

---

## 2. Controles implementados

### 2.1 Autenticación

- JWT en guard `api` con secret rotable (`php artisan jwt:secret`)
- Blacklist activa: logout invalida el token en backend
- `JWT_LOCK_SUBJECT=true` previene suplantación entre modelos
- `JWT_REFRESH_TTL=20160` (14 días) limita ventana de re-auth automática

### 2.2 Autorización

- Middleware `permission:<NAME>` aplicado por ruta
- Frontend valida en guard de router (`meta.permission`) y en componentes (`isPermission()`)
- Doble validación: si frontend falla, backend bloquea igual

### 2.3 Validación de entrada

- `FormRequest` en flujos críticos (login, registro, 2FA, productos parciales)
- Validación heterogénea en otros módulos — ver §6

### 2.4 Cifrado y secretos

- `APP_KEY` y `JWT_SECRET` exclusivos por entorno
- `two_factor_secret_encrypted` y `two_factor_pending_secret_encrypted` cifrados con `Crypt::encryptString()`
- Las contraseñas usan `Hash::make()` (bcrypt 12)

---

## 3. Política de contraseñas

### 3.1 Configuración recomendada

| Aspecto | Valor recomendado |
|---|---|
| Longitud mínima | 12 caracteres |
| Complejidad | Mayúsculas + minúsculas + dígitos + símbolos |
| Rotación | 90 días para roles administrativos |
| Reutilización | Bloquear las últimas 5 contraseñas |
| Bloqueo | 5 intentos fallidos → throttle 1 minuto |

### 3.2 Estado actual

- Throttling activo en login (5 intentos/min)
- Las demás reglas (longitud, complejidad, rotación, reutilización) **NO están activas** por defecto
- **Pendiente**: implementar middleware `PasswordPolicy` aplicado al endpoint de cambio de contraseña

---

## 4. Política 2FA

### 4.1 Activación

Cualquier usuario puede activar 2FA desde su perfil. **Recomendación**: hacerlo obligatorio para roles `Super-Admin`, `Gerente`, `Auditor`.

### 4.2 Códigos de recuperación

- 8 códigos generados al activar 2FA
- Single-use, hasheados en `user_recovery_codes`
- Regenerables con `POST /auth/2fa/recovery/regenerate`

### 4.3 Recuperación si se pierde el dispositivo

1. Usar uno de los códigos de recuperación
2. Si no hay códigos: contactar Super-Admin para reset manual:
   ```bash
   php artisan tinker
   >>> $u = User::find($id);
   >>> $u->two_factor_enabled = false;
   >>> $u->two_factor_secret_encrypted = null;
   >>> $u->save();
   >>> $u->recoveryCodes()->delete();
   ```

---

## 5. Auditoría

### 5.1 Qué se audita

- Acceso a rutas protegidas (vía middleware `audit.route`)
- Cambios sobre modelos auditables: User, Product, Sale, Purchase, Transport, Refurbish
- Navegación del frontend (`POST /api/audit/navigation` con throttle 45s)

### 5.2 Datos guardados

- Quién (`actor_id`), qué (`auditable_type` + `auditable_id`), acción, IP, User-Agent, timestamp
- Para `update`: diff por campo en `audit_event_changes`

### 5.3 Datos sensibles excluidos

Por convención, NO deben auditarse cambios de:
- `password`
- `two_factor_secret_encrypted`, `two_factor_pending_secret_encrypted`
- `remember_token`

> **Verificación pendiente**: confirmar que el observer de auditoría filtra estos campos. Caso contrario, agregar lista de campos `$hidden` en el observer.

### 5.4 Acceso y exportación

- Visualización: `/audit/logs` (permiso `view_audit_logs`)
- Exportación: `/audit/logs/export?format=xlsx|csv` (permiso `export_audit_logs`)
- Cada exportación queda registrada en `audit_exports`

### 5.5 Retención

- Política recomendada: 12 meses online + 5-7 años en almacenamiento frío
- Implementación pendiente: comando `php artisan audit:archive --older-than=365`

---

## 6. Hallazgos y plan de remediación

> Resumen del informe de auditoría técnica del 2026-03-24 (versión consolidada).

### 6.1 Matriz de hallazgos

| ID | Hallazgo | Severidad | Estado | Acción |
|---|---|---|---|---|
| H-01 | JWT en localStorage (riesgo XSS) | Alta | Abierto | Migrar a cookie HttpOnly + anti-CSRF |
| H-02 | Logout no siempre invalida token en backend | Media | Mitigado | Forzar `/auth/logout` en frontend |
| H-03 | Ruta `POST /refurbish/start/{id}` sin método | Alta | **Resuelto** | Método `start` implementado en `RefurbishController` |
| H-04 | Typos en `ConversionController` (`oderBy`, `user->id`) | Alta | **Resuelto** | `orderBy` y `auth('api')->user()->id` corregidos |
| H-05 | Riesgo de null en `TransportDetailController::attentionDelivery` | Alta | **Resuelto** | Encapsulado en `DB::transaction`, `lockForUpdate`, validación de estados |
| H-06 | Inconsistencia `date_emission` vs `date_emition` (compras) | Media | Abierto | Unificar contrato |
| H-07 | Orden de rutas en `audit/logs/export` vs `audit/logs/{id}` | Media | Resuelto | Static antes que paramétrica |
| H-08 | Permisos amplios (`all`) en CRM y Refurbish | Media | Parcial | Granular por acción |
| H-09 | `Route::resource` expone rutas innecesarias | Baja-Media | Abierto | Migrar a `Route::apiResource` |
| H-10 | Naming legacy (`puchase`, `warehause`, `sucuarsal`) | Media | Aceptado | Plan de normalización por fases |
| H-11 | Validaciones heterogéneas en endpoints críticos | Media | Parcial | Estandarizar con `FormRequest` |
| H-12 | Bugs HTTP 200 con body status=403 (BUG-01..04 QA Sprint 1) | Alta | Resuelto | Sprint 1.2 — verificado |

### 6.2 Detalle por hallazgo crítico

#### H-01 — Token en localStorage (abierto)

**Riesgo**: XSS captura token y secuestra sesión.
**Plan**:
1. Migrar emisión de JWT a cookie `Set-Cookie: token=<jwt>; HttpOnly; Secure; SameSite=Strict`
2. Implementar protección CSRF (token sincronizador en header `X-CSRF-TOKEN`)
3. Frontend deja de leer/escribir `localStorage.token`
4. Mantener compat layer temporal durante despliegue

#### H-03 — Ruta sin implementación (resuelto)

`RefurbishController::start()` está implementado y funcionando. Ruta `POST /api/refurbish/start/{id}` operativa con permiso `register_refurbish`.

#### H-04 — `ConversionController` (resuelto)

`orderBy('id', 'desc')` correctamente escrito en `index()`. `auth('api')->user()` (con paréntesis) en `store()`. Adicionalmente se aplica `DB::transaction` y `lockForUpdate` sobre `product_warehouses`.

#### H-05 — Null en traslado (resuelto)

`TransportDetailController::attentionDelivery` ahora usa `DB::transaction` con `lockForUpdate` sobre `transport_details` y `transports`, valida los estados (1=salida pendiente, 3=ya entregado) antes de operar, y usa `$this->stockService->increaseOrCreate()` para crear stock destino de forma segura.

---

## 7. Checklist de hardening para producción

### 7.1 Servidor

- [ ] Sistema operativo actualizado (`unattended-upgrades` activo)
- [ ] Firewall configurado (UFW o iptables): solo 22, 80, 443 abiertos al exterior
- [ ] SSH solo con clave (deshabilitar password login)
- [ ] Fail2ban activo para SSH y Nginx
- [ ] Usuario no-root para despliegue (ej. `Sitecsas`)
- [ ] Logs del sistema en `/var/log/syslog` con rotación

### 7.2 Aplicación backend

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `APP_KEY` único y fuerte (`php artisan key:generate`)
- [ ] `JWT_SECRET` único y fuerte (`php artisan jwt:secret`)
- [ ] `LOG_LEVEL=error`, `LOG_CHANNEL=daily`
- [ ] CORS configurado solo para el dominio del frontend
- [ ] Rate limiting verificado bajo carga real
- [ ] Migraciones aplicadas con `--force`
- [ ] Caché de config, rutas, vistas y eventos aplicada
- [ ] Storage NO accesible directamente vía URL (excepto `storage/app/public` con symlink controlado)
- [ ] `/storage` y `/bootstrap/cache` con permisos 775 owner web

### 7.3 Aplicación frontend

- [ ] `dist/` servido por Nginx con HTTPS
- [ ] Headers de seguridad activos (ver §7.5)
- [ ] No hay variables `VITE_*` con secretos (todo prefijo `VITE_` se incluye en bundle público)
- [ ] Source maps deshabilitados o protegidos en producción
- [ ] Dependencias auditadas: `pnpm audit --audit-level=high`

### 7.4 Base de datos

- [ ] Usuario dedicado por aplicación (no usar `postgres` superusuario)
- [ ] Conexión SSL si la BD está en otro host
- [ ] Backups diarios automáticos (ver [docs/OPERACIONES.md](./OPERACIONES.md))
- [ ] Retención de backups: 30 días incremental + 12 meses semanal
- [ ] Acceso al puerto 5432 solo desde IPs autorizadas
- [ ] Logs de conexiones activos (`log_connections=on`)

### 7.5 Headers de seguridad (Nginx)

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.sistema-inventario.com" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

> Ajustar `script-src` y `connect-src` al dominio real. Idealmente eliminar `'unsafe-inline'` migrando estilos y scripts inline a archivos externos.

### 7.6 Aplicativo

- [ ] Contraseña del Super-Admin demo cambiada
- [ ] 2FA activo en todos los usuarios administrativos
- [ ] Permisos revisados: nadie tiene más permisos de los necesarios
- [ ] Roles dummy/test eliminados antes de ir a producción
- [ ] Seeders de demo NO ejecutados en producción
- [ ] Worker de colas activo si hay tareas asíncronas
- [ ] Cron de Laravel registrado

---

## 8. Respuesta a incidentes

### 8.1 Sospecha de cuenta comprometida

1. Forzar logout del usuario:
   ```bash
   php artisan tinker
   >>> JWTAuth::invalidate('TOKEN_DEL_USUARIO')
   ```
2. Resetear su contraseña
3. Forzar reactivación de 2FA si tenía
4. Revisar `audit_events` con filtro por `actor_id` para detectar acciones sospechosas
5. Notificar al usuario y al responsable de seguridad

### 8.2 Sospecha de breach (filtración de secrets)

1. Rotar `APP_KEY` y `JWT_SECRET` inmediatamente:
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```
   > **Atención**: rotar `APP_KEY` invalida todos los datos cifrados existentes (ej. 2FA secrets). Re-encrypt manual si es necesario.
2. Forzar logout global (truncar `jwt_blacklist` no — agregar todos los tokens activos a blacklist o cambiar `JWT_SECRET` que invalida todo)
3. Revisar logs de Nginx y Laravel buscando IPs anómalas
4. Comunicar a usuarios afectados según política de la organización

### 8.3 SQL injection / acceso no autorizado a BD

1. Aislar la aplicación (apagar servicio temporalmente)
2. Hacer snapshot de la BD para análisis forense
3. Revisar logs de PostgreSQL: `pg_log/` o `journalctl -u postgresql`
4. Restaurar desde último backup limpio si es necesario
5. Auditar todas las queries afectadas

### 8.4 DoS o abuso de API

1. Activar rate limiting más agresivo temporalmente en `app/Http/Kernel.php`
2. Bloquear IP atacante en firewall: `ufw deny from <IP>`
3. Fail2ban regla custom para 429 repetidos
4. Si persiste, usar Cloudflare o WAF en front

---

## Referencias

- Informe original de auditoría: `INFORME_SEGURIDAD_ROBUSTEZ.md` (archivo en `docs/_archivo/`)
- Reporte QA Sprint 1: `REPORTE_QA_SPRINT1_API_LOCAL_2026-04-08.md` (archivo en `docs/_archivo/`)
- [Manual de instalación](./INSTALACION.md)
- [Configuración](./CONFIGURACION.md)
- [Manual de operaciones](./OPERACIONES.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

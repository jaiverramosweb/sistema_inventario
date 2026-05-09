# Manual de Operaciones (Runbook)

> **Sistema de Inventario Pro** — Procedimientos operativos para mantenimiento, monitoreo y recuperación.

---

## Tabla de contenidos

1. [Servicios y procesos críticos](#1-servicios-y-procesos-críticos)
2. [Tareas diarias / semanales / mensuales](#2-tareas-diarias--semanales--mensuales)
3. [Backups y restauración](#3-backups-y-restauración)
4. [Logs](#4-logs)
5. [Monitoreo y alertas](#5-monitoreo-y-alertas)
6. [Despliegue y rollback](#6-despliegue-y-rollback)
7. [Mantenimiento de la base de datos](#7-mantenimiento-de-la-base-de-datos)
8. [Rotación de claves y secretos](#8-rotación-de-claves-y-secretos)
9. [Escalado](#9-escalado)
10. [Troubleshooting de producción](#10-troubleshooting-de-producción)
11. [Contactos y escalamiento](#11-contactos-y-escalamiento)

---

## 1. Servicios y procesos críticos

| Servicio | Cómo verificar | Cómo reiniciar |
|---|---|---|
| Nginx | `systemctl status nginx` | `sudo systemctl restart nginx` |
| PHP-FPM 8.2 | `systemctl status php8.2-fpm` | `sudo systemctl restart php8.2-fpm` |
| PostgreSQL | `systemctl status postgresql` | `sudo systemctl restart postgresql` |
| Redis (si aplica) | `systemctl status redis-server` | `sudo systemctl restart redis-server` |
| Laravel queue worker | `systemctl status inventario-queue` | `sudo systemctl restart inventario-queue` |
| Cron de Laravel | `crontab -l` | Recargar crontab |

### 1.1 Health check rápido

```bash
# 1. Backend responde
curl -fsS https://api.sistema-inventario.com/up || echo "BACKEND DOWN"

# 2. Frontend responde
curl -fsS https://app.sistema-inventario.com | head -n 1 || echo "FRONTEND DOWN"

# 3. BD acepta conexiones
sudo -u postgres psql -d sistema_inventario -c "SELECT 1;" || echo "DB DOWN"

# 4. Disco
df -h | awk '$5+0 > 80 {print "WARN disco "$0}'

# 5. Memoria
free -m | awk '/^Mem/ {if ($3/$2*100 > 85) print "WARN memoria " int($3/$2*100)"%"}'
```

> Recomendado: encapsular en `/usr/local/bin/inventario-health.sh` y ejecutar via cron cada 5 min, alertando a Slack/email si falla.

---

## 2. Tareas diarias / semanales / mensuales

### Diarias

- [ ] Verificar que el backup automático de BD se ejecutó (revisar logs)
- [ ] Revisar errores en `storage/logs/laravel-YYYY-MM-DD.log` (>= ERROR)
- [ ] Verificar uso de disco no exceda 80%
- [ ] Confirmar uptime del servicio (Nginx, PHP-FPM, PostgreSQL)

### Semanales

- [ ] Revisar tabla `audit_events` para crecimiento anómalo
- [ ] Revisar tabla `failed_jobs` (si se usa cola): `SELECT count(*) FROM failed_jobs;`
- [ ] Revisar últimas releases en `/var/www/sistema_inventario/releases/`
- [ ] Validar backups: hacer un test de restore en entorno de staging
- [ ] Revisar logs de Nginx por IPs sospechosas: `awk '{print $1}' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head`

### Mensuales

- [ ] Aplicar parches de seguridad del SO: `sudo apt update && sudo apt upgrade`
- [ ] Auditar dependencias: `composer audit` y `pnpm audit`
- [ ] Limpiar releases viejas (deploy.sh ya conserva 3, verificar)
- [ ] Vaciar tabla `personal_access_tokens` revocados (si aplica)
- [ ] Reportar métricas: ventas registradas, usuarios activos, errores
- [ ] Revisar y rotar logs grandes
- [ ] Snapshot completo de BD para archivo

### Trimestrales

- [ ] Revisar política de retención de auditoría
- [ ] Auditoría de roles y permisos: ¿hay usuarios con privilegios excesivos?
- [ ] Pruebas de DR (Disaster Recovery): restaurar desde backup en servidor frío
- [ ] Revisión completa de [docs/SEGURIDAD.md §7](./SEGURIDAD.md#7-checklist-de-hardening-para-producción)

---

## 3. Backups y restauración

### 3.1 Backup automático de PostgreSQL (cron)

```bash
# /etc/cron.d/inventario-backup
0 3 * * * postgres /usr/local/bin/inventario-backup.sh >> /var/log/inventario-backup.log 2>&1
```

`/usr/local/bin/inventario-backup.sh`:

```bash
#!/bin/bash
set -e
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/inventario"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

pg_dump -U inventario_user -d sistema_inventario \
  --format=custom --compress=9 \
  --file="$BACKUP_DIR/sistema_inventario_$TIMESTAMP.dump"

# Limpiar backups viejos
find "$BACKUP_DIR" -name "sistema_inventario_*.dump" -mtime +$RETENTION_DAYS -delete

# Subir a S3 (opcional)
aws s3 cp "$BACKUP_DIR/sistema_inventario_$TIMESTAMP.dump" \
  s3://backups-inventario/postgres/ --storage-class STANDARD_IA

echo "[OK] Backup $TIMESTAMP completado"
```

### 3.2 Backup de archivos (storage/)

```bash
# /usr/local/bin/inventario-storage-backup.sh
#!/bin/bash
set -e
TIMESTAMP=$(date +%Y%m%d)
tar -czf "/var/backups/inventario/storage_$TIMESTAMP.tar.gz" \
  -C /var/www/sistema_inventario/shared storage/

aws s3 cp "/var/backups/inventario/storage_$TIMESTAMP.tar.gz" \
  s3://backups-inventario/storage/
```

Programar a las 03:30 diarias.

### 3.3 Restauración

```bash
# 1. Detener la aplicación
sudo systemctl stop nginx
sudo systemctl stop php8.2-fpm

# 2. Restaurar BD
pg_restore -U inventario_user -d sistema_inventario \
  --clean --if-exists \
  /var/backups/inventario/sistema_inventario_20260509_030000.dump

# 3. Restaurar storage
cd /var/www/sistema_inventario/shared
sudo rm -rf storage
sudo tar -xzf /var/backups/inventario/storage_20260509.tar.gz

# 4. Limpiar caché de Laravel
cd /var/www/sistema_inventario/current/admin-back
php artisan optimize:clear

# 5. Reiniciar
sudo systemctl start php8.2-fpm
sudo systemctl start nginx

# 6. Verificar
curl -fsS https://api.sistema-inventario.com/up
```

### 3.4 Política recomendada

| Tipo | Frecuencia | Retención |
|---|---|---|
| BD completa (full dump) | Diaria 03:00 | 30 días local + 90 días S3 |
| BD incremental WAL | Continuo (si se configura) | 7 días |
| Storage (archivos generados) | Diaria 03:30 | 30 días local + 12 meses S3 |
| Snapshot mensual | Día 1 cada mes | 5 años (compliance) |

---

## 4. Logs

### 4.1 Ubicaciones

| Componente | Ruta |
|---|---|
| Laravel app | `/var/www/sistema_inventario/shared/storage/logs/laravel-YYYY-MM-DD.log` |
| Nginx access | `/var/log/nginx/access.log` |
| Nginx error | `/var/log/nginx/error.log` |
| PHP-FPM error | `/var/log/php8.2-fpm.log` |
| PostgreSQL | `/var/log/postgresql/postgresql-14-main.log` |
| Cron | `/var/log/syslog` (filtrar por `cron`) |
| Sistema | `journalctl -u <servicio>` |

### 4.2 Rotación

Configurar `logrotate`:

```
# /etc/logrotate.d/inventario
/var/www/sistema_inventario/shared/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
```

### 4.3 Búsquedas frecuentes

```bash
# Errores en las últimas 24h
grep -E "ERROR|CRITICAL|EMERGENCY" /var/www/sistema_inventario/shared/storage/logs/laravel-$(date +%F).log

# IPs con más requests en última hora
awk -v ts="$(date -d '1 hour ago' '+%d/%b/%Y:%H')" '$4 ~ ts {print $1}' \
  /var/log/nginx/access.log | sort | uniq -c | sort -rn | head

# Endpoints con más 5xx
awk '$9 ~ /^5/ {print $7}' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head

# Slow queries en PostgreSQL
sudo grep "duration:" /var/log/postgresql/postgresql-14-main.log | awk '{print $NF}' | sort -rn | head
```

### 4.4 Agregación recomendada (producción)

Migrar logs a una pila ELK (Elasticsearch + Logstash + Kibana) o a un servicio gestionado (Datadog, Better Stack, Loki). Beneficios: búsqueda rápida, alertas automáticas, retención prolongada.

---

## 5. Monitoreo y alertas

### 5.1 Métricas mínimas a monitorear

| Métrica | Umbral de alerta |
|---|---|
| Disponibilidad HTTP (uptime) | < 99.5% en 5 min |
| Latencia p95 backend | > 1s |
| Tasa de errores 5xx | > 1% en 5 min |
| Uso de CPU | > 80% por 10 min |
| Uso de RAM | > 85% por 10 min |
| Uso de disco | > 80% |
| Conexiones a PostgreSQL | > 80% del max_connections |
| Tamaño de `audit_events` | Crecimiento > 5x el promedio |
| Cola de jobs (`failed_jobs`) | > 10 jobs fallidos |

### 5.2 Herramientas sugeridas

| Caso de uso | Herramienta |
|---|---|
| Uptime externo | UptimeRobot, BetterUptime, Pingdom |
| Métricas + dashboards | Grafana + Prometheus |
| APM (Application Performance Monitoring) | Datadog APM, New Relic, Sentry |
| Errores en runtime | Sentry, Bugsnag |
| Logs centralizados | Datadog, Loki, ELK |
| Notificaciones | Slack, email, PagerDuty |

### 5.3 Configuración mínima de uptime

Endpoint dedicado para monitoreo: `GET /api/up` (Laravel 11+ trae `/up` por defecto). Configurar UptimeRobot a checkear cada 1 min con alerta a Slack y email.

---

## 6. Despliegue y rollback

### 6.1 Despliegue normal

Ver flujo completo en [docs/INSTALACION.md §6](./INSTALACION.md#6-despliegue-en-producción-deploysh). Resumen:

```bash
# Desde el directorio temp_deploy (donde llega el código nuevo)
chmod +x deploy.sh
./deploy.sh
```

### 6.2 Rollback rápido

Si una nueva release rompe producción:

```bash
cd /var/www/sistema_inventario
ls -t releases/ | head -5      # Ver releases disponibles

# Cambiar symlink a release anterior
RELEASE_PREV=releases/20260508120000   # ajustar
sudo rm current
sudo ln -s "$PWD/$RELEASE_PREV" current

# Limpiar caché
cd current/admin-back
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

> **Importante**: si la release nueva incluyó migraciones, el rollback de código NO revierte el schema. Coordinar con DBA o tener migraciones reversibles (`down()` implementado).

### 6.3 Rollback de migración

```bash
cd /var/www/sistema_inventario/current/admin-back
php artisan migrate:rollback --step=1     # solo última batch
```

> Solo funciona si la migración tiene `down()` correctamente implementado y los datos lo permiten.

---

## 7. Mantenimiento de la base de datos

### 7.1 Vacuum y analyze

PostgreSQL hace autovacuum, pero en tablas grandes puede ayudar manual:

```sql
VACUUM ANALYZE audit_events;
VACUUM ANALYZE sales;
VACUUM ANALYZE products;
```

Ejecutar mensualmente en horario de bajo tráfico.

### 7.2 Reindex

Si los índices están fragmentados:

```sql
REINDEX TABLE audit_events;
REINDEX TABLE sale_details;
```

### 7.3 Identificar tablas pesadas

```sql
SELECT relname AS tabla,
       pg_size_pretty(pg_total_relation_size(relid)) AS tamaño,
       n_live_tup AS filas
FROM pg_stat_user_tables
ORDER BY pg_total_relation_size(relid) DESC
LIMIT 20;
```

### 7.4 Slow queries

Habilitar logging de queries lentas en `postgresql.conf`:

```
log_min_duration_statement = 1000     # 1s
log_statement = 'mod'                 # log de DML
log_line_prefix = '%t [%p]: user=%u,db=%d,app=%a '
```

Reiniciar PostgreSQL y revisar `/var/log/postgresql/postgresql-14-main.log`.

### 7.5 Archivado de auditoría

Implementar comando Artisan (pendiente):

```bash
php artisan audit:archive --older-than=365 --target=s3://archive-inventario/
```

Alternativa SQL manual:

```sql
-- Mover registros antiguos a tabla de archivo
INSERT INTO audit_events_archive
SELECT * FROM audit_events WHERE created_at < NOW() - INTERVAL '12 months';

DELETE FROM audit_events WHERE created_at < NOW() - INTERVAL '12 months';

VACUUM FULL audit_events;
```

---

## 8. Rotación de claves y secretos

### 8.1 Rotar `JWT_SECRET`

```bash
cd /var/www/sistema_inventario/current/admin-back
php artisan jwt:secret --force
php artisan config:cache
sudo systemctl restart php8.2-fpm
```

> **Efecto**: invalida TODOS los tokens activos. Comunicar a usuarios o programar fuera de horario laboral.

### 8.2 Rotar `APP_KEY`

⚠️ **Riesgoso**: rotar `APP_KEY` invalida todos los datos cifrados con la clave anterior (sesiones, 2FA secrets, ciertos campos).

Procedimiento seguro:
1. Re-encrypt datos antes de rotar (script custom)
2. Cambiar la clave
3. Verificar funcionalidad

Para emergencias (no datos cifrados sensibles):
```bash
php artisan key:generate --force
```

### 8.3 Rotar contraseñas DB

1. Crear nuevo usuario o cambiar password en PostgreSQL:
   ```sql
   ALTER USER inventario_user WITH PASSWORD 'NUEVA_PASSWORD_SEGURA';
   ```
2. Actualizar `/var/www/sistema_inventario/shared/.env`
3. Reiniciar PHP-FPM: `sudo systemctl restart php8.2-fpm`
4. Verificar: `curl https://api.sistema-inventario.com/up`

### 8.4 Política

| Secreto | Frecuencia recomendada |
|---|---|
| `APP_KEY` | Solo en emergencia (alto riesgo) |
| `JWT_SECRET` | Cada 6-12 meses |
| `DB_PASSWORD` | Cada 6 meses |
| Certificados SSL | Auto-renovado (Let's Encrypt 90d) |
| Contraseñas usuarios admin | 90 días |

---

## 9. Escalado

### 9.1 Escalado vertical (single-node)

Aumentar CPU/RAM del VPS. Sin cambios en aplicación. Recomendado hasta ~200 usuarios concurrentes.

### 9.2 Escalado horizontal

Pre-requisitos:
- Caché y sesiones en Redis (no en BD)
- Storage en disco compartido (NFS) o S3
- Load balancer (Nginx, HAProxy o cloud LB)

Topología:

```
            [Cloud LB]
           /          \
    [Backend N1]  [Backend N2]   ← stateless, comparten Redis y BD
           \          /
              [Redis]
              [PostgreSQL primary]
              [PostgreSQL replica] (read-only para reportes)
```

### 9.3 Optimizaciones progresivas

1. CDN para frontend (Cloudflare / CloudFront)
2. Caché de respuestas API costosas (KPI dashboard) en Redis con TTL corto
3. Replica de lectura PostgreSQL para reportes
4. Particionamiento de tabla `audit_events` por mes
5. Cola de trabajos (importaciones, exports grandes, generación de PDFs en lote)

---

## 10. Troubleshooting de producción

### 10.1 502 Bad Gateway

**Causa**: PHP-FPM caído o saturado.
**Diagnóstico**:
```bash
sudo systemctl status php8.2-fpm
sudo tail -100 /var/log/php8.2-fpm.log
```
**Solución**: reiniciar FPM. Si se cae recurrentemente, aumentar `pm.max_children` en `/etc/php/8.2/fpm/pool.d/www.conf`.

### 10.2 504 Gateway Timeout

**Causa**: query lenta o request que excede `fastcgi_read_timeout`.
**Diagnóstico**: revisar slow queries (§7.4) y logs de Laravel.
**Solución**: optimizar query (índice o reescritura) o aumentar timeout temporalmente.

### 10.3 La auditoría no aparece en el menú lateral

Ver checklist específico: ver `docs/_archivo/CHECKLIST_VERIFICACION_MENU_AUDITORIA_PRODUCCION.md`. Resumen:

```bash
cd /var/www/sistema_inventario/current/admin-back

# 1. Confirmar permiso existe
php artisan tinker --execute="echo Spatie\Permission\Models\Permission::where('name','view_audit_logs')->where('guard_name','api')->exists() ? 'OK' : 'FALTA';"

# 2. Asignar permiso al rol del usuario
php artisan tinker --execute="\$r=Spatie\Permission\Models\Role::where('name','NOMBRE_ROL')->where('guard_name','api')->first(); \$r->givePermissionTo('view_audit_logs');"

# 3. Limpiar caché
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 4. Re-login en frontend
# (cerrar sesión en navegador y volver a entrar — los permisos se cargan en localStorage al login)
```

### 10.4 Disco lleno

**Diagnóstico**:
```bash
df -h
sudo du -sh /var/www/sistema_inventario/* | sort -rh | head
sudo du -sh /var/log/* | sort -rh | head
```

**Soluciones rápidas**:
- Limpiar releases viejas: `cd /var/www/sistema_inventario/releases && ls -dt */ | tail -n +4 | xargs sudo rm -rf`
- Limpiar logs viejos: `sudo journalctl --vacuum-time=7d`
- Truncar logs grandes: `sudo truncate -s 0 /var/log/nginx/access.log` (rotación logrotate evita esto)

### 10.5 Error 500 al iniciar sesión

**Causa común**: `JWT_SECRET` faltante o config caché desactualizado.
```bash
cd /var/www/sistema_inventario/current/admin-back
php artisan jwt:secret --show          # confirma que existe
php artisan optimize:clear
php artisan config:cache
sudo systemctl restart php8.2-fpm
```

### 10.6 PostgreSQL conexiones agotadas

**Síntoma**: `FATAL: sorry, too many clients already`
**Diagnóstico**:
```sql
SELECT count(*) FROM pg_stat_activity;
SHOW max_connections;
```
**Solución**:
- Identificar y matar conexiones idle:
  ```sql
  SELECT pg_terminate_backend(pid)
  FROM pg_stat_activity
  WHERE state = 'idle' AND state_change < NOW() - INTERVAL '1 hour';
  ```
- Aumentar `max_connections` en `postgresql.conf` (cuidado con RAM)
- Implementar connection pooler (PgBouncer)

---

## 11. Contactos y escalamiento

> **Completar al firmar el acta de entrega.**

| Nivel | Responsable | Contacto | Cuándo escalar |
|---|---|---|---|
| L1 — Soporte usuario | Mesa de ayuda interna | `<email>` | Dudas de uso, errores de UI |
| L2 — Operaciones TI | Administrador del sistema | `<email>` | Caídas, errores intermitentes |
| L3 — Desarrollo | Equipo de desarrollo | `<email>` | Bugs reproducibles, mejoras |
| L4 — Proveedor | SI Sistemas Informáticos y Tecnología SAS | `<email>` | Garantía, escalamiento crítico |

### 11.1 SLA recomendado

| Severidad | Tiempo respuesta | Tiempo resolución |
|---|---|---|
| Crítica (sistema caído) | < 15 min | < 4 h |
| Alta (función crítica afectada) | < 1 h | < 1 día hábil |
| Media (función no crítica) | < 4 h | < 3 días hábiles |
| Baja (mejora, duda) | < 1 día | Próximo sprint |

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Configuración](./CONFIGURACION.md)
- [Seguridad y hardening](./SEGURIDAD.md)
- [Plan de QA](./PLAN_QA.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

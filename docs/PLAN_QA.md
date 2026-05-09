# Plan y Reporte de QA

> **Sistema de Inventario Pro** — Estrategia de calidad, casos críticos y resultado de validaciones ejecutadas.

---

## Tabla de contenidos

1. [Objetivo](#1-objetivo)
2. [Alcance](#2-alcance)
3. [Niveles de prueba](#3-niveles-de-prueba)
4. [Herramientas](#4-herramientas)
5. [Tests automatizados implementados](#5-tests-automatizados-implementados)
6. [Casos críticos manuales](#6-casos-críticos-manuales)
7. [Reporte de Sprint 1 (API local)](#7-reporte-de-sprint-1-api-local)
8. [Reporte de Sprint 1.2 (parche HTTP)](#8-reporte-de-sprint-12-parche-http)
9. [Bugs abiertos](#9-bugs-abiertos)
10. [Próximos pasos](#10-próximos-pasos)

---

## 1. Objetivo

Garantizar que el sistema entregado cumple con los requisitos funcionales, no funcionales y de seguridad acordados, y que los flujos críticos para la operación del cliente (ventas, compras, traslados, devoluciones, reacondicionamiento) operen sin pérdida de datos ni inconsistencias.

---

## 2. Alcance

### 2.1 Módulos validados

| Módulo | Estado |
|---|---|
| Auth + 2FA | ✅ Validado |
| Compras + atención de detalle | ✅ Validado |
| Ventas + pagos | ✅ Validado |
| Conversiones de unidad | ✅ Validado |
| Traslados (salida y recepción) | ✅ Validado |
| Kardex | ✅ Validado |
| Productos (CRUD + import) | ⚠️ Parcial — pendiente import masivo Excel |
| Devoluciones | ⚠️ Parcial |
| CRM (leads, oportunidades) | ⚠️ Parcial |
| Reacondicionamiento | ⚠️ Parcial |
| Auditoría (visualización + export) | ✅ Validado |
| Roles y permisos (CRUD + asignación) | ✅ Validado |
| KPI Dashboard | ⚠️ Parcial — pendiente performance bajo carga |

### 2.2 Fuera de alcance

- Pruebas de carga / estrés (no ejecutadas)
- Pruebas de penetración (no ejecutadas — recomendadas antes de exponer a internet)
- Compatibilidad cross-browser (validado solo Chromium)
- Compatibilidad mobile responsive (no exhaustivo)

---

## 3. Niveles de prueba

| Nivel | Cobertura | Responsable |
|---|---|---|
| Unit (Backend) | Helpers y servicios aislados | Desarrollo |
| Feature (Backend) | Endpoints API con BD | Desarrollo |
| E2E (Frontend) | Flujos completos via Playwright | Desarrollo + QA |
| Manual exploratorio | Validación de UI y casos límite | QA |
| UAT | Validación funcional con usuario final | Cliente |

---

## 4. Herramientas

| Herramienta | Uso |
|---|---|
| PHPUnit 11.5+ | Tests unit/feature backend |
| Laravel Pint | Style checks |
| Playwright 1.59+ | E2E frontend (chromium) |
| MSW 2.2 | Mock de API en dev |
| ESLint / Stylelint | Linting frontend |
| Postman / Insomnia | Validación manual de API |
| pgAdmin / psql | Inspección de BD |

---

## 5. Tests automatizados implementados

### 5.1 Backend (PHPUnit)

> **Estado actual**: cobertura mínima. Tests `tests/Feature` y `tests/Unit` con casos de ejemplo. **Pendiente**: implementar suite completa.

**Casos prioritarios pendientes**:
- [ ] `AuthControllerTest` — login, logout, refresh, 2FA setup/verify
- [ ] `ProductImportTest` — import Excel con dataset válido e inválido
- [ ] `SaleTransactionTest` — crear venta descuenta stock, anular venta restituye
- [ ] `TransportFlowTest` — salida descuenta origen, recepción suma destino
- [ ] `ConversionRollbackTest` — eliminar conversión revierte stock
- [ ] `RefurbishCostTest` — agregar componente actualiza `refurbished_value`
- [ ] `PermissionGuardTest` — usuario sin permiso recibe 403

### 5.2 Frontend (Playwright)

Suite **`tests/e2e/smoke.spec.ts`** — pruebas de acceso a módulos críticos:

| Test | Estado |
|---|---|
| Login y cierre de sesión | ✅ Pasa |
| Acceso a `/configuration/sucursales` | ✅ Pasa |
| Acceso a `/product/list` | ✅ Pasa |
| Acceso a `/purchase/list` | ✅ Pasa |
| Acceso a `/sales/list` | ✅ Pasa |
| Acceso a `/transport/list` | ✅ Pasa |
| Acceso a `/crm/leads` | ✅ Pasa |
| Auditoría: acceso y export XLSX | ✅ Pasa |

**Ejecución**:

```bash
cd admin-front
pnpm run e2e:install                                    # primera vez
PW_E2E_PASSWORD="<password>" pnpm run e2e:smoke         # ejecuta suite
PW_E2E_PASSWORD="<password>" pnpm run e2e:smoke:headed  # con browser visible
```

**Cobertura pendiente**:
- [ ] Crear venta completa (cliente + detalles + pago)
- [ ] Crear compra y atender detalle
- [ ] Crear traslado, salida y recepción
- [ ] Workbench de reacondicionamiento (agregar/retirar componente)
- [ ] CRUD de roles y permisos
- [ ] Conversión de leads a clientes

---

## 6. Casos críticos manuales

### 6.1 Autenticación

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| AUTH-01 | Login válido | Email + password correctos | 200 + JWT |
| AUTH-02 | Login con credenciales inválidas | Password incorrecto | 401 + mensaje |
| AUTH-03 | Login con 5 intentos fallidos | 5 intentos en < 1 min | 429 throttle |
| AUTH-04 | Login con 2FA | Email + pwd + código TOTP | 200 + JWT |
| AUTH-05 | Login con código TOTP inválido | Código aleatorio | 401 |
| AUTH-06 | Login con código de recuperación | Usar uno de los 8 codes | 200 + JWT, código marcado como usado |
| AUTH-07 | Token expirado | Esperar 8h | 401 + redirect a login |
| AUTH-08 | Refresh dentro de TTL | `POST /auth/refresh` | Nuevo token válido |
| AUTH-09 | Logout | `POST /auth/logout` con token válido | 200, token blacklisted |
| AUTH-10 | Acceso post-logout | Reusar token tras logout | 401 |

### 6.2 Productos

| # | Caso | Resultado esperado |
|---|---|---|
| PROD-01 | Crear producto válido | 201 + producto en BD |
| PROD-02 | Crear con SKU duplicado | 422 |
| PROD-03 | Editar producto | 200 + cambios persistidos + audit_event registrado |
| PROD-04 | Eliminar producto | 200 + soft delete (`deleted_at` set) |
| PROD-05 | Listar con filtros | 200 + datos coherentes |
| PROD-06 | Importar Excel válido | Productos creados en chunks de 1000 |
| PROD-07 | Importar Excel con error en fila X | Resto se importa, fila X reportada |
| PROD-08 | Exportar a Excel | Descarga inicia |

### 6.3 Ventas

| # | Caso | Resultado esperado |
|---|---|---|
| SAL-01 | Crear venta con stock suficiente | 201 + stock descontado |
| SAL-02 | Crear venta con stock insuficiente | 403 con mensaje |
| SAL-03 | Crear cotización (state=2) | 201, NO descuenta stock |
| SAL-04 | Convertir cotización a venta | Stock descontado |
| SAL-05 | Registrar pago parcial | 200 + state_mayment=2 |
| SAL-06 | Sobrepago | **Antes**: 200+body 403. **Después** (Sprint 1.2): 403 |
| SAL-07 | Anular venta | Stock restituido |
| SAL-08 | Generar PDF de venta | Descarga de PDF válido |

### 6.4 Compras

| # | Caso | Resultado esperado |
|---|---|---|
| PUR-01 | Crear compra | 201 |
| PUR-02 | Atender detalle (recepción) | Stock incrementado en almacén destino |
| PUR-03 | Re-atender detalle ya recibido | **Antes**: 200+body 403. **Después** (Sprint 1.2): 403 |
| PUR-04 | Generar PDF de compra | Descarga válida |

### 6.5 Traslados

| # | Caso | Resultado esperado |
|---|---|---|
| TRA-01 | Crear traslado | 201, state=1 (Solicitud) |
| TRA-02 | Marcar salida sin stock | 403 |
| TRA-03 | Marcar salida con stock | Stock descontado en origen |
| TRA-04 | Marcar recepción sin salida previa | **Antes**: 200+body 403. **Después** (Sprint 1.2): 403 |
| TRA-05 | Marcar recepción con salida realizada | Stock incrementado en destino |
| TRA-06 | PDF de traslado | Descarga válida |

### 6.6 Conversiones

| # | Caso | Resultado esperado |
|---|---|---|
| CONV-01 | Convertir con stock disponible | Stock origen ↓, stock destino ↑ |
| CONV-02 | Convertir sin stock | **Antes**: 200+body 403. **Después** (Sprint 1.2): 403 |
| CONV-03 | Eliminar conversión | Stock revertido al estado anterior |

### 6.7 Auditoría

| # | Caso | Resultado esperado |
|---|---|---|
| AUD-01 | Crear producto y revisar log | Evento `created` registrado en `audit_events` |
| AUD-02 | Editar producto y revisar log | Evento `updated` con diff de campos |
| AUD-03 | Filtrar logs por usuario | Solo eventos del actor seleccionado |
| AUD-04 | Exportar logs XLSX | Descarga archivo + registro en `audit_exports` |
| AUD-05 | Acceder sin permiso `view_audit_logs` | 403 + redirect not-authorized |

---

## 7. Reporte de Sprint 1 (API local)

**Fecha**: 2026-04-08
**Entorno**: local (`127.0.0.1:8000/api`)
**Usuario**: `superadmin@sitecsas.com`
**Tag de dataset**: `QA-S1-20260408092453`

### 7.1 Ejecución

Validación API exhaustiva sobre módulos críticos (auth, compras, ventas, conversiones, traslados, kardex). Ver detalle del dataset y comandos en `docs/_archivo/REPORTE_QA_SPRINT1_API_LOCAL_2026-04-08.md`.

### 7.2 Resultados

| Módulo | Casos ejecutados | Pasaron | Fallaron |
|---|---|---|---|
| Auth | 5 | 5 | 0 |
| Compras + atención | 3 | 2 | 1 (BUG-01) |
| Ventas + pagos | 5 | 4 | 1 (BUG-02) |
| Conversiones | 3 | 2 | 1 (BUG-03) |
| Traslados | 4 | 3 | 1 (BUG-04) |
| Kardex | 1 | 1 | 0 |

### 7.3 Bugs encontrados

Todos los bugs comparten el mismo patrón: el endpoint **bloquea correctamente** la operación a nivel de negocio, pero devuelve **HTTP 200** con un body que indica `status: 403`. Esto rompe clientes que dependen del status HTTP para control de flujo.

| ID | Endpoint | Escenario |
|---|---|---|
| BUG-01 | `POST /pushase-details/attention` | Re-atención de detalle ya recibido |
| BUG-02 | `POST /sale-payments` | Sobrepago |
| BUG-03 | `POST /conversions` | Sin stock disponible |
| BUG-04 | `POST /transport-details/attention-delivery` | Recepción sin salida previa |

---

## 8. Reporte de Sprint 1.2 (parche HTTP)

**Fecha**: 2026-04-08
**Resultado**: parche aplicado y verificado.

### 8.1 Verificación antes/después

| ID | Antes | Después |
|---|---|---|
| BUG-01 | HTTP 200 + body `status=403` | HTTP 403 + body compatible |
| BUG-02 | HTTP 200 + body `status=403` | HTTP 403 + body compatible |
| BUG-03 | HTTP 200 + body `status=403` | HTTP 403 + body compatible |
| BUG-04 | HTTP 200 + body `status=403` | HTTP 403 + body compatible |

### 8.2 Estado final Sprint 1

✅ Los 4 bugs críticos resueltos. Validación API básica aprobada.

---

## 9. Bugs abiertos

> Inventariados en [docs/SEGURIDAD.md §6](./SEGURIDAD.md#6-hallazgos-y-plan-de-remediación).

| ID | Hallazgo | Severidad | Estado |
|---|---|---|---|
| H-01 | Token JWT en localStorage | Alta | Abierto — migración a cookie HttpOnly fuera de alcance estándar |
| H-03 | `POST /refurbish/start/{id}` sin método | Alta | **Resuelto** |
| H-04 | Typos en `ConversionController` | Alta | **Resuelto** |
| H-05 | Riesgo null en `TransportDetailController::attentionDelivery` | Alta | **Resuelto** |
| H-06 | `date_emission` vs `date_emition` (compras) | Media | Abierto — unificar contrato |
| H-08 | Permisos amplios (`all`) en CRM/Refurbish | Media | Parcial — granularizar |
| H-09 | `Route::resource` con rutas no usadas | Baja-Media | Abierto — migrar a `apiResource` |
| H-11 | Validaciones heterogéneas | Media | Parcial — estandarizar `FormRequest` |

---

## 10. Próximos pasos

### 10.1 Pruebas pendientes antes de producción

- [ ] Suite completa de tests Feature en backend
- [ ] Cobertura E2E ampliada (CRUD de venta, compra, traslado, refurbish)
- [ ] Pruebas de carga (Locust o JMeter) — al menos 50 req/s en endpoints críticos
- [ ] Pruebas de penetración (OWASP Top 10)
- [ ] Validación de UAT con cliente sobre dataset real

### 10.2 Mejoras al proceso

- [ ] CI con ejecución automática de tests en cada PR
- [ ] Dashboard de cobertura de código (Codecov o similar)
- [ ] Reporte de regresión post-deploy
- [ ] Integración Sentry para captura automática de errores en producción

---

## Referencias

- Reporte detallado Sprint 1: `docs/_archivo/REPORTE_QA_SPRINT1_API_LOCAL_2026-04-08.md`
- Checklist auditoría producción: `docs/_archivo/CHECKLIST_VERIFICACION_MENU_AUDITORIA_PRODUCCION.md`
- [Hallazgos de seguridad](./SEGURIDAD.md)
- [Manual de operaciones](./OPERACIONES.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

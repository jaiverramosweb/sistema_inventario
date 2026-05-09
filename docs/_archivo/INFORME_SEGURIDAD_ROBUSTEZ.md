# Informe de Seguridad y Robustez

Proyecto: Sistema de Inventario Pro  
Fecha: 2026-03-24  
Alcance: `admin-back` + `admin-front`

---

## 1) Objetivo

Este documento resume hallazgos tecnicos que afectan seguridad, estabilidad y mantenibilidad de la plataforma, y propone acciones concretas para corregirlos.

Se enfoca en:

- Riesgos de seguridad aplicativa.
- Bugs funcionales con impacto operativo.
- Inconsistencias de contrato API y deuda tecnica.
- Plan de correccion por prioridad.

---

## 2) Resumen ejecutivo

El sistema ya tiene una base solida (JWT, 2FA, RBAC, auditoria), pero existen puntos que deben corregirse para elevar seguridad y robustez:

- Hay endpoints/rutas inconsistentes o incompletas que pueden provocar errores en runtime.
- Hay deuda de naming y contratos front/back que incrementa riesgo de fallos silenciosos.
- Existen oportunidades de hardening en seguridad de sesion y control de permisos.
- Faltan pruebas automatizadas de flujos criticos (ventas, compras, traslados, devoluciones).

---

## 3) Hallazgos de seguridad

## 3.1 JWT en `localStorage` (riesgo XSS)

- Impacto: si ocurre XSS, un atacante puede robar el token y secuestrar sesion.
- Evidencia: el frontend usa token bearer persistido en cliente para llamar API.
- Recomendacion:
  - Migrar a cookie `HttpOnly` + `Secure` + `SameSite=Strict` para token de sesion.
  - Implementar estrategia anti-CSRF si se usa cookie de autenticacion.
  - Reducir TTL de access token y reforzar refresh controlado.

## 3.2 Logout no siempre invalida token en backend

- Impacto: sesiones pueden quedar validas en backend aun si frontend limpia storage.
- Recomendacion:
  - Estandarizar cierre de sesion para SIEMPRE llamar `/api/auth/logout`.
  - En fallback (error de red), limpiar storage local y marcar sesion invalida de forma explicita.

## 3.3 Permisos muy amplios en modulos sensibles

- Impacto: uso de permiso general (`all`) puede exponer funciones a perfiles no deseados.
- Evidencia: menu de CRM y reacondicionamiento con controles muy amplios.
- Recomendacion:
  - Definir permisos granulares por accion (`list`, `create`, `update`, `delete`, `change_stage`, etc.).
  - Alinear `meta.permission` frontend con middleware/policies backend.

## 3.4 Superficie API innecesaria por `Route::resource`

- Impacto: se publican rutas `create/edit` de patron web que no se usan en API JSON.
- Recomendacion:
  - Cambiar de `Route::resource` a `Route::apiResource` donde aplique.
  - Eliminar rutas no utilizadas para reducir superficie y ruido en auditoria.

## 3.5 Orden de rutas audit (posible conflicto)

- Impacto: `GET /audit/logs/export` puede chocar con `GET /audit/logs/{id}` segun orden.
- Evidencia: en `admin-back/routes/api.php` la ruta parametrica aparece antes de `logs/export`.
- Recomendacion:
  - Declarar rutas estaticas antes que parametrizadas.
  - Mover `GET /audit/logs/export` por encima de `GET /audit/logs/{id}`.

Ejemplo sugerido:

```php
Route::get('logs/export', [AuditExportController::class, 'export'])
    ->middleware('permission:export_audit_logs')
    ->name('audit.logs.export');

Route::get('logs/{id}', [AuditLogController::class, 'show'])->name('audit.logs.show');
```

## 3.6 Validaciones heterogeneas en endpoints criticos

- Impacto: reglas inconsistentes entre controladores aumentan riesgo de datos invalidos.
- Recomendacion:
  - Estandarizar con `FormRequest` en ventas, compras, traslados, conversiones y devoluciones.
  - Unificar formato de error JSON (codigo, mensaje, detalles por campo).

---

## 4) Bugs funcionales y errores tecnicos

## 4.1 Ruta declarada sin implementacion

- Hallazgo: `POST /api/refurbish/start/{id}` esta en rutas, pero `start()` no existe en `RefurbishController`.
- Impacto: error en runtime si se invoca.
- Solucion:
  - O implementar `start()` con validaciones y transaccion.
  - O eliminar la ruta si no sera usada.

## 4.2 Errores en `ConversionController`

- Hallazgos:
  - `oderBy` (typo) en `index`.
  - `auth('api')->user->id` en `store` (acceso incorrecto; falta `user()`).
- Impacto: fallos de consulta y/o null access.
- Solucion:
  - Corregir a `orderBy`.
  - Corregir a `auth('api')->user()->id`.

## 4.3 Riesgo de null en entrega de traslado

- Hallazgo: en `TransportDetailController::attentionDelivery` hay escenario donde se crea stock destino, pero se usa variable previa sin refrescar.
- Impacto: error en runtime o actualizacion incompleta de stock.
- Solucion:
  - Reasignar variable tras `create()`.
  - Encapsular en transaccion y agregar guard clause.

## 4.4 Inconsistencia de campos de fecha en compras

- Hallazgo: frontend maneja `date_emission` y backend espera `date_emition`.
- Impacto: perdida de dato o validacion incorrecta.
- Solucion:
  - Unificar contrato en un solo nombre.
  - Mantener compatibilidad temporal aceptando ambos durante migracion.

## 4.5 Duplicidad de ruta de update en usuarios

- Hallazgo: existe `POST /users/{id}` ademas de update de resource (`PUT/PATCH`).
- Impacto: confusion de contrato, mantenimiento mas costoso.
- Solucion:
  - Estandarizar update en `PUT/PATCH /users/{id}`.
  - Deprecar `POST /users/{id}` con ventana de transicion.

## 4.6 Typos y naming legacy en entidades/rutas/campos

- Hallazgos recurrentes: `puchase`, `pushases`, `refound`, `paiment`, `warehause`, `sucuarsal`.
- Impacto: errores humanos, mayor tiempo de onboarding, bugs por mapeo.
- Solucion:
  - Plan de normalizacion por fases (alias temporales + migracion gradual + retiro).

---

## 5) Plan de accion recomendado

## 5.1 Prioridad alta (Semana 1-2)

1. Corregir bugs bloqueantes de conversiones y traslados.
2. Resolver ruta de reacondicionamiento sin implementacion (`start`).
3. Corregir orden de rutas de auditoria (`export` antes de `{id}`).
4. Estandarizar update de usuarios y contrato de fecha en compras.

## 5.2 Prioridad media (Semana 3-6)

1. Migrar rutas API a `apiResource` donde corresponda.
2. Reforzar permisos granulares en CRM/reacondicionamiento.
3. Unificar validaciones con `FormRequest` en modulos criticos.
4. Estandarizar respuesta de errores JSON.

## 5.3 Prioridad estrategica (1-3 meses)

1. Plan de hardening de sesion (cookie HttpOnly o estrategia equivalente robusta).
2. Suite de pruebas automatizadas de flujos de negocio.
3. Refactor de naming legacy con compatibilidad backward controlada.
4. Versionado formal de API (`/api/v1`).

---

## 6) Guia practica de implementacion

## 6.1 Checklist tecnico por pull request

- [ ] Toda nueva ruta usa permiso explicito y policy/gate.
- [ ] No se agregan rutas `create/edit` en API JSON.
- [ ] Validaciones en `FormRequest` para endpoints de escritura.
- [ ] Respuesta de error sigue formato estandar.
- [ ] Cambio cubierto con prueba (unitaria o feature).
- [ ] Se revisa impacto en frontend (contratos y nombres de campos).

## 6.2 Formato estandar de error recomendado

```json
{
  "ok": false,
  "code": "VALIDATION_ERROR",
  "message": "Datos invalidos",
  "errors": {
    "field": ["Mensaje de validacion"]
  }
}
```

## 6.3 Politica de naming sugerida

- Entidades y rutas en ingles consistente (`purchase`, `refund`, `payment`, `warehouse`, `branch`).
- Campos en `snake_case` sin typos.
- Alias temporales solo durante periodo de migracion.

---

## 7) Matriz resumida de hallazgos

| Id | Hallazgo | Severidad | Impacto | Accion recomendada |
|---|---|---|---|---|
| H-01 | JWT en localStorage | Alta | Secuestro de sesion ante XSS | Migrar a cookie HttpOnly/Secure + anti-CSRF |
| H-02 | Logout no uniforme | Media | Sesiones no invalidadas consistentemente | Forzar `/auth/logout` siempre |
| H-03 | Ruta `refurbish/start` sin metodo | Alta | Error runtime | Implementar o eliminar ruta |
| H-04 | Typos en ConversionController | Alta | Fallo funcional | Corregir `orderBy` y `user()` |
| H-05 | Null risk en traslado entrega | Alta | Error runtime / stock inconsistente | Reasignar variable + transaccion |
| H-06 | Fecha compra inconsistente | Media | Datos incompletos | Unificar contrato front/back |
| H-07 | Orden de rutas audit | Media | Resolucion incorrecta de ruta | Declarar `export` antes de `{id}` |
| H-08 | Permisos `all` en modulos | Media | Exceso de privilegios | Permisos granulares |
| H-09 | `resource` en API con create/edit | Baja-Media | Superficie y ruido | Migrar a `apiResource` |
| H-10 | Naming legacy inconsistente | Media | Deuda acumulada | Plan de normalizacion por fases |

---

## 8) Conclusion

La plataforma tiene una base funcional madura y buena direccion de seguridad (JWT + 2FA + RBAC), pero para llevarla a un nivel mas robusto en produccion se recomienda ejecutar un plan de remediacion por fases:

1) corregir bugs criticos y rutas inconsistentes,  
2) endurecer control de sesion y permisos,  
3) estandarizar contratos/validaciones y automatizar pruebas.

Con este plan, el sistema mejora resiliencia operativa, reduce incidentes y facilita crecimiento sostenido.

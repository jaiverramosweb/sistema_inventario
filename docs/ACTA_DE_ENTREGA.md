# Acta de Entrega

## Sistema de Inventario Pro

---

### Datos del proveedor

| Campo | Detalle |
|---|---|
| Razón social | **SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS** |
| NIT | **900.583.147-1** |
| Producto entregado | Sistema de Inventario Pro (admin-back + admin-front) |
| Fecha de entrega | _____ / _____ / _____ |

### Datos del cliente

| Campo | Detalle |
|---|---|
| Razón social | _________________________________________ |
| NIT | _________________________________________ |
| Representante | _________________________________________ |
| Cargo | _________________________________________ |
| Correo electrónico | _________________________________________ |
| Teléfono | _________________________________________ |

---

## 1. Objeto

El proveedor entrega al cliente el **Sistema de Inventario Pro**, una plataforma integral de gestión de inventarios desarrollada en Laravel 12 (backend) + Vue 3 (frontend), con cobertura funcional sobre los siguientes módulos:

1. Dashboard con KPIs y gráficos comerciales
2. Gestión de productos, categorías, marcas y modelos
3. Gestión de almacenes y stock por ubicación
4. Kardex de movimientos
5. Ventas y cotizaciones con clientes y pagos
6. Compras y recepción de mercancía
7. Traslados entre almacenes con salida y entrega
8. Devoluciones (RMA) con clasificación
9. Reacondicionamiento técnico de equipos
10. CRM con leads, oportunidades y pipeline
11. Auditoría de eventos y cambios
12. Configuración (sucursales, almacenes, categorías, unidades, proveedores)
13. Roles y permisos granulares
14. Gestión de usuarios con autenticación JWT y 2FA opcional

---

## 2. Componentes entregados

### 2.1 Código fuente

- Repositorio Git completo con:
  - `admin-back/` — API REST en Laravel 12
  - `admin-front/` — SPA en Vue 3 + Vuetify 3
  - `deploy.sh` — script de despliegue blue-green
  - `docs/` — documentación técnica versionada

### 2.2 Documentación técnica (en repositorio, formato Markdown)

| Documento | Contenido |
|---|---|
| `docs/INSTALACION.md` | Manual de instalación dev y producción |
| `docs/CONFIGURACION.md` | Variables de entorno, JWT, 2FA, roles y permisos |
| `docs/MODELO_BD.md` | Esquema relacional y diccionario de datos |
| `docs/API.md` | Catálogo de endpoints REST |
| `docs/ARQUITECTURA.md` | Visión técnica integral |
| `docs/SEGURIDAD.md` | Controles, hallazgos y hardening |
| `docs/OPERACIONES.md` | Runbook: backups, logs, monitoreo, rollback |
| `docs/PLAN_QA.md` | Plan y reportes de pruebas |
| `docs/MANUAL_USUARIO.md` | Guía de uso por módulo con capturas |
| `docs/CONTRIBUCION.md` | Convenciones para futuros desarrolladores |

### 2.3 Documentación para el cliente (Word)

Carpeta `entrega_cliente/`:

1. `01_Manual_de_Instalacion.docx`
2. `02_Manual_de_Configuracion.docx`
3. `03_Modelo_de_Base_de_Datos.docx`
4. `04_Manual_de_Usuario.docx`
5. `05_Manual_Tecnico_Arquitectura.docx`
6. `06_Manual_de_Operaciones.docx`
7. `07_Plan_y_Reporte_de_QA.docx`
8. `08_Checklist_de_Seguridad.docx`
9. `09_API_REST.docx`
10. `10_Licencias_y_Dependencias.docx`
11. `11_Acta_de_Entrega.docx` (este documento)

### 2.4 Recursos adicionales

- Plantilla de importación de productos: `ejemplo_import.xlsx`
- Capturas del sistema: `entrega_cliente/recursos/`
- Documentos legacy consolidados: `docs/_archivo/`

---

## 3. Credenciales de acceso

> **Las credenciales se entregan por canal seguro separado** (sobre cerrado, gestor de contraseñas o correo cifrado), NO en este documento.

| Tipo | Detalle |
|---|---|
| Usuario administrador inicial | `superadmin@sitecsas.com` |
| Rol | Super-Admin (todos los permisos) |
| URL del sistema | A definir según ambiente |

> **Es responsabilidad del cliente cambiar la contraseña del usuario administrador inicial inmediatamente después de la primera sesión y activar la autenticación de dos factores (2FA) en todos los usuarios administrativos.**

---

## 4. Pruebas ejecutadas

Se ejecutaron y aprobaron las siguientes baterías de pruebas:

- **Sprint 1 (API local)** — autenticación, compras, ventas, conversiones, traslados, kardex
- **Sprint 1.2 (parche HTTP)** — corrección de códigos HTTP en endpoints transaccionales
- **Smoke E2E (Playwright)** — acceso a módulos críticos desde el frontend

Detalle completo: `docs/PLAN_QA.md`.

---

## 5. Soporte y garantía

### 5.1 Garantía

> **Completar según términos comerciales acordados.**

| Aspecto | Detalle |
|---|---|
| Período de garantía | _____________ meses desde la fecha de entrega |
| Cobertura | Bugs reproducibles que afecten funcionalidad descrita en este acta |
| Exclusiones | Cambios no acordados, modificaciones post-entrega por terceros, problemas de infraestructura del cliente |

### 5.2 Soporte

| Nivel | Cobertura | Contacto |
|---|---|---|
| L1 — Soporte usuario | Mesa de ayuda interna del cliente | A cargo del cliente |
| L2 — Operaciones TI | Administración del sistema | A cargo del cliente |
| L3 — Desarrollo y proveedor | Bugs reproducibles, mejoras, garantía | SI Sistemas Informáticos y Tecnología SAS |

### 5.3 SLA recomendado

| Severidad | Tiempo de respuesta | Tiempo de resolución |
|---|---|---|
| Crítica (sistema caído) | < 1 hora | < 1 día hábil |
| Alta (función crítica afectada) | < 4 horas | < 3 días hábiles |
| Media (función no crítica) | < 1 día hábil | Próxima iteración |
| Baja (mejora, duda) | < 2 días hábiles | Backlog |

---

## 6. Limitaciones conocidas

Se entregan documentadas en `docs/SEGURIDAD.md §6` y `docs/PLAN_QA.md §9`. Resumen:

| ID | Hallazgo | Plan acordado |
|---|---|---|
| H-01 | Token JWT en localStorage (riesgo XSS) | Migración a cookies HttpOnly — fuera de alcance de garantía estándar |
| H-03 | Ruta `POST /refurbish/start/{id}` sin método | Implementación o eliminación — siguiente sprint |
| H-04 | Typos en `ConversionController` | Corrección — siguiente sprint |
| H-05 | Riesgo null en `TransportDetailController::attentionDelivery` | Refactor — siguiente sprint |
| Naming legacy | `puchase`, `warehause`, etc. | Aceptado — plan de migración a futuro |

---

## 7. Recomendaciones

Para asegurar continuidad operativa, el proveedor recomienda al cliente:

1. **Activar 2FA** en todos los usuarios administrativos antes de salir a producción.
2. **Cambiar la contraseña del Super-Admin demo** inmediatamente.
3. **Configurar backups automáticos** de PostgreSQL siguiendo `docs/OPERACIONES.md §3`.
4. **Implementar monitoreo de uptime** con alertas (UptimeRobot, BetterUptime u otro).
5. **Configurar HTTPS** con certificado válido (Let's Encrypt o comercial).
6. **Aplicar el checklist de hardening** de `docs/SEGURIDAD.md §7` antes de exponer el sistema a internet.
7. **Capacitar al equipo de soporte L1** usando el `docs/MANUAL_USUARIO.md`.
8. **Mantener actualizadas** las dependencias (composer y pnpm) con auditoría trimestral.
9. **Programar pruebas de Disaster Recovery** semestrales — restaurar desde backup en servidor frío.
10. **Considerar pruebas de penetración** (pentest) antes de exponer datos sensibles.

---

## 8. Conformidad y firmas

Las partes manifiestan conformidad con el contenido entregado y suscriben la presente acta como evidencia de la entrega.

\

**Por SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS:**

\
\
\

________________________________________
Nombre: _________________________________
Cargo: _________________________________
Fecha: _________________________________

\
\

**Por el cliente:**

\
\
\

________________________________________
Nombre: _________________________________
Cargo: _________________________________
Fecha: _________________________________

---

*Documento generado el 2026-05-09 como parte de la entrega documental del proyecto Sistema de Inventario Pro.*

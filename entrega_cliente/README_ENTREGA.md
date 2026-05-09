# Entrega Documental — Sistema de Inventario Pro

**Proveedor**: SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS — NIT 900.583.147-1
**Fecha de entrega**: 2026-05-09

---

## Contenido de esta carpeta

| # | Documento | Descripción |
|---|---|---|
| 01 | `01_Manual_de_Instalacion.docx` | Instalación completa en desarrollo y producción, requisitos, despliegue blue-green |
| 02 | `02_Manual_de_Configuracion.docx` | Variables de entorno, JWT, 2FA, roles, parámetros de negocio |
| 03 | `03_Modelo_de_Base_de_Datos.docx` | Diagrama ER y diccionario de datos por tabla |
| 04 | `04_Manual_de_Usuario.docx` | **Guía de uso por módulo con capturas de pantalla** |
| 05 | `05_Manual_Tecnico_Arquitectura.docx` | Visión arquitectónica integral, ADRs, deuda técnica |
| 06 | `06_Manual_de_Operaciones.docx` | Runbook: backups, logs, monitoreo, rollback |
| 07 | `07_Plan_y_Reporte_de_QA.docx` | Plan de pruebas y resultados de Sprint 1 / 1.2 |
| 08 | `08_Checklist_de_Seguridad.docx` | Controles aplicados y guía de hardening |
| 09 | `09_API_REST.docx` | Catálogo de endpoints con métodos, permisos y ejemplos |
| 10 | `10_Licencias_y_Dependencias.docx` | Software open source utilizado y licencias |
| 11 | `11_Acta_de_Entrega.docx` | Acta formal de conformidad para firma |
| 12 | `12_CICD_GitHub_Actions.docx` | Pipeline de despliegue automático y guía de migración a otro repositorio |

### Carpeta `recursos/`

- **`sistema-inventario.postman_collection.json`** — Colección Postman/Insomnia importable con todos los endpoints principales y flujo de login automatizado
- **`reference.docx`** — Plantilla Word con branding aplicada a todos los `.docx` (no editar)
- **`customize_reference.py`** — Script para regenerar la plantilla branded
- **34 capturas PNG** del sistema usadas en el Manual de Usuario

Listado de capturas:

| # | Pantalla |
|---|---|
| 01 | login |
| 02 | dashboard |
| 03 | productos listado |
| 04 | productos crear |
| 05 | clientes listado |
| 06 | ventas listado |
| 07 | ventas crear |
| 08 | compras listado |
| 09 | traslados listado |
| 10 | refurbish listado |
| 11 | crm leads |
| 12 | crm pipeline |
| 13 | kardex |
| 14 | auditoría logs |
| 15 | configuración sucursales |
| 16 | usuarios |
| 17 | roles y permisos |
| 18 | perfil |
| 19 | configuración almacenes |
| 20 | conversiones |
| 21 | devoluciones |
| 22 | configuración categorías |
| 23 | configuración unidades |
| 24 | configuración proveedores |
| 25 | configuración conversiones de unidad |
| 26 | pantalla "no autorizado" (403) |
| 27 | producto editar |
| 28 | venta detalle / editar |
| 29 | compra crear |
| 30 | traslado crear |
| 31 | refurbish workbench |
| 32 | cliente modal crear |
| 33 | usuario modal crear |
| 34 | rol modal permisos |

---

## Orden de lectura recomendado

### Para el responsable del proyecto

1. `11_Acta_de_Entrega.docx` — qué se entrega y bajo qué condiciones
2. `04_Manual_de_Usuario.docx` — qué hace el sistema
3. `08_Checklist_de_Seguridad.docx` — qué controles tenés activos

### Para el área de TI / sysadmin

1. `01_Manual_de_Instalacion.docx`
2. `02_Manual_de_Configuracion.docx`
3. `06_Manual_de_Operaciones.docx`
4. `08_Checklist_de_Seguridad.docx`
5. `03_Modelo_de_Base_de_Datos.docx` (referencia para auditorías)

### Para el equipo de desarrollo (si extiende el sistema)

1. `05_Manual_Tecnico_Arquitectura.docx`
2. `03_Modelo_de_Base_de_Datos.docx`
3. `09_API_REST.docx`
4. `07_Plan_y_Reporte_de_QA.docx`

### Para los usuarios finales

- `04_Manual_de_Usuario.docx` — único documento que necesitan

---

## Documentos en el repositorio

Los documentos `.docx` de esta carpeta se generaron a partir de los `.md` versionados en el repositorio (`docs/`). Si en el futuro se actualiza la documentación, el repositorio es la **fuente de verdad** y los Word se regeneran con:

```bash
# Requiere pandoc instalado
cd docs
pandoc <archivo>.md -o ../entrega_cliente/<destino>.docx --from=gfm --to=docx --toc --toc-depth=3
```

---

## Credenciales y secretos

> **Las credenciales NO están en estos documentos.** Se entregan por canal seguro separado (gestor de contraseñas, sobre cerrado o correo cifrado).

Por seguridad, al recibir las credenciales:

1. Cambiar la contraseña del usuario administrador inicial
2. Activar 2FA en todos los usuarios administrativos
3. Generar y guardar los códigos de recuperación en lugar seguro

---

## Soporte

Para consultas sobre el contenido entregado, garantía o soporte técnico, contactar a:

**SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS**
NIT: 900.583.147-1

> Datos de contacto específicos según términos comerciales acordados en el `11_Acta_de_Entrega.docx`.

---

*Entrega generada el 2026-05-09.*

# Sistema de Inventario Pro

Sistema integral de gestión de inventarios diseñado para centralizar el control de existencias, compras, ventas, traslados y procesos especializados de reacondicionamiento técnico.

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 + Laravel 12 |
| Frontend | Vue 3 + Vuetify 3 + Vite |
| Base de datos | PostgreSQL 14+ |
| Autenticación | JWT + 2FA (TOTP) |
| Autorización | Spatie Permission (RBAC granular) |
| Despliegue | Releases blue-green con Nginx + PHP-FPM |

---

## Quickstart (desarrollo)

```bash
# Backend
cd admin-back
composer install
cp .env.example .env
php artisan key:generate && php artisan jwt:secret
# (Configurá DB_* en .env)
php artisan migrate --seed
php artisan serve

# Frontend (en otra terminal)
cd admin-front
pnpm install
pnpm run dev
```

- Backend: http://127.0.0.1:8000
- Frontend: http://localhost:5173
- Usuario inicial: `superadmin@sitecsas.com` (ver seeder para password de demo)

> Para producción y troubleshooting consultar [docs/INSTALACION.md](./docs/INSTALACION.md).

---

## Documentación

Documentación técnica completa en `docs/`:

| Documento | Contenido |
|---|---|
| [INSTALACION.md](./docs/INSTALACION.md) | Manual de instalación dev y producción |
| [CICD.md](./docs/CICD.md) | Despliegue automático con GitHub Actions + cómo migrar a otro repo |
| [CONFIGURACION.md](./docs/CONFIGURACION.md) | Variables de entorno, JWT, 2FA, roles y permisos |
| [MODELO_BD.md](./docs/MODELO_BD.md) | Esquema relacional y diccionario de datos |
| [API.md](./docs/API.md) | Catálogo de endpoints REST |
| [ARQUITECTURA.md](./docs/ARQUITECTURA.md) | Visión técnica integral |
| [SEGURIDAD.md](./docs/SEGURIDAD.md) | Controles, hallazgos y hardening |
| [OPERACIONES.md](./docs/OPERACIONES.md) | Runbook: backups, logs, monitoreo |
| [PLAN_QA.md](./docs/PLAN_QA.md) | Plan y reportes de pruebas |
| [MANUAL_USUARIO.md](./docs/MANUAL_USUARIO.md) | Guía de uso por módulo |
| [CONTRIBUCION.md](./docs/CONTRIBUCION.md) | Convenciones de desarrollo |

Documentos para entrega al cliente (Word) en `entrega_cliente/`.

---

## Módulos del sistema

1. **Dashboard** — KPIs, gráficos de ventas/compras/asesores
2. **Productos** — catálogo, stock por almacén, precios por sucursal
3. **Almacenes** — control de existencias por ubicación
4. **Kardex** — historial de movimientos
5. **Ventas** — transacciones, clientes, pagos
6. **Compras** — proveedores, recepción de mercancía
7. **Traslados** — movimientos entre almacenes con salida y entrega
8. **Devoluciones** — RMA con clasificación (reparación/reemplazo/devolución)
9. **Reacondicionamiento** — workbench técnico para equipos refurbished
10. **CRM** — leads, oportunidades, pipeline kanban, actividades
11. **Auditoría** — eventos y diff de cambios con exportación
12. **Configuración** — sucursales, almacenes, categorías, unidades, proveedores
13. **Roles y permisos** — 41 permisos granulares con backfill automático
14. **Usuarios** — gestión y asignación de roles

---

## Estructura del repositorio

```
proyecto_inventario/
├── admin-back/                 # API REST — Laravel 12
├── admin-front/                # SPA — Vue 3 + Vuetify
├── docs/                       # Documentación técnica (Markdown)
│   └── _archivo/               # Documentos previos consolidados
├── entrega_cliente/            # Documentos Word para entrega final
├── deploy.sh                   # Script de despliegue blue-green
└── README.md                   # Este archivo
```

---

## Comandos frecuentes

### Backend

```bash
php artisan migrate --seed          # Migrar y poblar BD
php artisan jwt:secret              # Generar nuevo JWT_SECRET
php artisan optimize:clear          # Limpiar todos los cachés
php artisan permission:cache-reset  # Reset cache de Spatie
php artisan test                    # Ejecutar tests
./vendor/bin/pint                   # Formato de código (PSR-12)
```

### Frontend

```bash
pnpm run dev          # Dev server (puerto 5173)
pnpm run build        # Build de producción → dist/
pnpm run preview      # Servir dist/ localmente
pnpm run lint         # ESLint + auto-fix
pnpm run e2e:smoke    # Tests E2E (Playwright)
```

---

## Soporte

| Nivel | Cuándo |
|---|---|
| Mesa de ayuda | Dudas de uso, errores de UI |
| Operaciones TI | Caídas, errores intermitentes |
| Desarrollo | Bugs reproducibles, mejoras |
| Proveedor (SI Sistemas) | Garantía, escalamiento crítico |

Procedimientos detallados en [docs/OPERACIONES.md §11](./docs/OPERACIONES.md#11-contactos-y-escalamiento).

---

## Licencia

Software propietario. Todos los derechos reservados.
SI SISTEMAS INFORMATICOS Y TECNOLOGIA SAS — NIT 900.583.147-1

---

*Desarrollado con pasión para la eficiencia operativa.*

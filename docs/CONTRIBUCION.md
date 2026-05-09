# Guía de Contribución

> **Sistema de Inventario Pro** — Cómo trabajar en el código del proyecto.

---

## Tabla de contenidos

1. [Setup local](#1-setup-local)
2. [Flujo de trabajo Git](#2-flujo-de-trabajo-git)
3. [Convenciones de código](#3-convenciones-de-código)
4. [Convenciones de commits](#4-convenciones-de-commits)
5. [Pull Requests](#5-pull-requests)
6. [Testing](#6-testing)
7. [Estándares de revisión](#7-estándares-de-revisión)
8. [Onboarding rápido](#8-onboarding-rápido)

---

## 1. Setup local

Ver [docs/INSTALACION.md](./INSTALACION.md) para los pasos completos.

Resumen:

```bash
# Backend
cd admin-back
composer install
cp .env.example .env
php artisan key:generate && php artisan jwt:secret
php artisan migrate --seed
php artisan serve

# Frontend (en otra terminal)
cd admin-front
pnpm install
pnpm run dev
```

---

## 2. Flujo de trabajo Git

### 2.1 Branching

- `main` — rama estable, refleja producción
- `develop` (opcional) — rama de integración
- `feature/<ticket>-<descripcion-corta>` — desarrollo de funcionalidad
- `fix/<ticket>-<descripcion-corta>` — corrección de bug
- `hotfix/<descripcion>` — corrección urgente sobre `main`
- `chore/<descripcion>` — tareas auxiliares (deps, configs)
- `docs/<descripcion>` — solo documentación

### 2.2 Workflow

```bash
# 1. Actualizar main
git checkout main
git pull

# 2. Crear rama
git checkout -b feature/INV-123-agregar-filtro-categoria

# 3. Desarrollar y commitear (ver §4)

# 4. Push y abrir PR
git push -u origin feature/INV-123-agregar-filtro-categoria
gh pr create
```

### 2.3 Convenciones específicas del repo

- No commitear directamente a `main`
- No hacer `git push --force` a ramas compartidas
- Antes de mergear, rebase contra `main` para mantener historia lineal
- Eliminar la rama remota tras mergear

---

## 3. Convenciones de código

### 3.1 Backend (PHP/Laravel)

**Estilo**: PSR-12 + Laravel Pint.

```bash
cd admin-back
./vendor/bin/pint              # auto-format
./vendor/bin/pint --test       # verifica sin modificar
```

**Reglas**:
- Nombres de clases en `PascalCase`
- Métodos y variables en `camelCase`
- Constantes en `UPPER_SNAKE_CASE`
- Modelos Eloquent en singular: `Product`, `Sale`
- Tablas en plural: `products`, `sales`
- FK en formato `<entidad>_id`: `product_id`
- Usar `FormRequest` para validación, NO validar en controlador
- Usar `Resource` para respuestas, NO devolver modelos crudos
- Lógica de negocio en `Services/`, NO en controladores
- Transacciones explícitas con `DB::transaction(fn() => ...)`

### 3.2 Frontend (Vue 3)

**Estilo**: ESLint + Stylelint + convención Composition API.

```bash
cd admin-front
pnpm run lint          # auto-fix
pnpm run qa:lint       # verificar
```

**Reglas**:
- Composition API con `<script setup>` siempre
- Componentes en `PascalCase` (`ProductForm.vue`)
- Composables en `camelCase` con prefijo `use` (`useProduct.js`)
- Stores Pinia con prefijo `use` (`useConfigStore`)
- Pages en `kebab-case` (auto-routing)
- Props tipadas con `defineProps<{...}>`
- Emits declarados con `defineEmits`
- Validación de permisos en UI con `isPermission('<NOMBRE>')`
- Llamadas API con `$api(...)` (NO usar `axios` directo)
- Definir `meta.permission` en cada page con `definePage()`

### 3.3 Naming legacy

**No introducir más typos**. Si tocás código con `puchase`, `warehause`, `sucuarsal`, etc., **mantener el typo** existente para no romper compatibilidad. La normalización es un proyecto separado con plan de migración por fases.

---

## 4. Convenciones de commits

Convencional Commits (sin Co-authored-by):

```
<tipo>(<scope opcional>): <descripción corta en presente, < 70 chars>

[cuerpo opcional explicando el porqué]

[footer opcional con BREAKING CHANGE: o referencias a tickets]
```

### Tipos

| Tipo | Uso |
|---|---|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `docs` | Solo documentación |
| `style` | Formato sin cambios funcionales |
| `refactor` | Cambio de código sin cambiar comportamiento |
| `perf` | Mejora de performance |
| `test` | Agregar o corregir tests |
| `chore` | Tareas auxiliares (deps, configs) |
| `build` | Cambios al build/CI |
| `revert` | Revertir un commit |

### Ejemplos

```
feat(productos): agregar filtro por categoría en listado

fix(auth): corregir acceso a auth('api')->user en ConversionController

docs(operaciones): documentar procedimiento de rollback

refactor(ventas): extraer cálculo de totales a SaleCalculator service
```

### Reglas

- Una funcionalidad/fix = un commit (idealmente)
- Mensajes en español o inglés, consistente por scope
- Sin emojis en mensajes
- Sin firmas `Co-authored-by:` ni atribuciones a IA

---

## 5. Pull Requests

### 5.1 Template recomendado

```markdown
## Resumen
<1-3 líneas explicando QUÉ y POR QUÉ>

## Cambios principales
- ...
- ...

## Cómo probar
1. ...
2. ...

## Checklist
- [ ] Lint pasa (`./vendor/bin/pint --test` y `pnpm run qa:lint`)
- [ ] Tests pasan (PHPUnit y/o Playwright)
- [ ] Documentación actualizada si aplica
- [ ] Migrations reversibles (con `down()` implementado)
- [ ] Sin secretos en el código
- [ ] Permisos correctamente configurados
```

### 5.2 Tamaño

- PR ideal: < 400 líneas modificadas
- PR > 400 líneas: dividir en stack o coordinar revisión extendida
- PR > 1000 líneas: justificar en el body por qué no se puede dividir

### 5.3 Revisores

- 1 revisor mínimo para cambios menores
- 2 revisores para cambios en flujos críticos (auth, ventas, compras, traslados)
- 1 revisor de seguridad para cambios en autenticación o permisos

---

## 6. Testing

### 6.1 Backend (PHPUnit)

```bash
cd admin-back
php artisan test                        # todos los tests
php artisan test --filter=AuthTest      # un test específico
php artisan test --coverage             # con coverage (requiere Xdebug)
```

**Convención**:
- Feature tests en `tests/Feature/<Modulo>Test.php`
- Unit tests en `tests/Unit/<Servicio>Test.php`
- Usar `RefreshDatabase` o `DatabaseTransactions` trait
- Usar Factories para crear data de prueba

**Mínimo esperado en PR de feature**:
- 1 happy path test
- 1 test de validación que falla
- 1 test de autorización (sin permiso → 403)

### 6.2 Frontend (Playwright)

```bash
cd admin-front
pnpm run e2e:install                                    # primera vez
PW_E2E_PASSWORD="<password>" pnpm run e2e:smoke         # ejecutar
PW_E2E_PASSWORD="<password>" pnpm run e2e:smoke:headed  # con browser visible
```

**Cobertura esperada**: tests de smoke (acceso a página) en cada nuevo módulo agregado.

---

## 7. Estándares de revisión

### 7.1 Bloqueantes (no se mergea sin resolver)

- Vulnerabilidad de seguridad (SQL injection, XSS, secret expuesto)
- Tests que no pasan
- Linter falla
- Lógica de permisos incorrecta o ausente
- Cambio de schema sin migración reversible
- Romper compatibilidad de contratos API sin coordinar

### 7.2 Sugerencias (no bloquean)

- Mejoras de naming
- Refactors menores
- Optimizaciones que no impactan funcionalidad

### 7.3 Lo que NO debe hacerse en PR

- Mezclar refactor + feature en el mismo PR
- Agregar dependencias sin justificación
- Modificar archivos no relacionados ("limpieza al paso")
- Subir archivos `.env`, `.key`, secretos
- Subir `node_modules/`, `vendor/`, `dist/`

---

## 8. Onboarding rápido

### 8.1 Lectura recomendada (en orden)

1. [README.md](../README.md) — visión general
2. [docs/INSTALACION.md](./INSTALACION.md) — setup local
3. [docs/ARQUITECTURA.md](./ARQUITECTURA.md) — visión técnica
4. [docs/MODELO_BD.md](./MODELO_BD.md) — esquema de datos
5. [docs/API.md](./API.md) — catálogo de endpoints
6. [docs/CONFIGURACION.md](./CONFIGURACION.md) — variables y permisos
7. Esta guía

### 8.2 Primer PR sugerido

- Tomar un bug de severidad baja del backlog
- Implementar el fix con su test
- Abrir PR siguiendo §5
- Iterar con feedback

### 8.3 Preguntas frecuentes

**¿Por qué algunos endpoints son `POST /products/index` en vez de `GET /products`?**
Convención del proyecto para listados con filtros complejos. El body lleva los filtros, evitando query strings largos. No es REST puro pero es consistente.

**¿Por qué hay typos en nombres de tablas?**
Legacy. No corregir sin plan de migración. Ver [docs/MODELO_BD.md §14](./MODELO_BD.md#14-notas-técnicas-e-inconsistencias).

**¿Cómo agrego un nuevo permiso?**
1. Agregar al seeder `PermissionsDemoSeeder` en `database/seeders/`
2. Ejecutar `php artisan db:seed --class=PermissionsDemoSeeder`
3. Agregar a `admin-front/src/utils/constants.js` para que aparezca en la UI de roles
4. Aplicar middleware `permission:NOMBRE` en la ruta backend
5. Aplicar `definePage({ meta: { permission: 'NOMBRE' } })` en la page

**¿Cómo creo una nueva ruta frontend?**
Crear el archivo `.vue` en `src/pages/<modulo>/<nombre>.vue`. El router se autogenera al guardar.

---

## Referencias

- [Manual de instalación](./INSTALACION.md)
- [Arquitectura](./ARQUITECTURA.md)
- [Modelo de datos](./MODELO_BD.md)
- [Plan de QA](./PLAN_QA.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

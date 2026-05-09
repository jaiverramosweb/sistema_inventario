# CI/CD — Despliegue automático con GitHub Actions

> **Sistema de Inventario Pro** — Documentación del pipeline de integración y despliegue continuo desde GitHub al VPS.

---

## Tabla de contenidos

1. [Resumen](#1-resumen)
2. [Arquitectura del pipeline](#2-arquitectura-del-pipeline)
3. [El workflow actual paso a paso](#3-el-workflow-actual-paso-a-paso)
4. [Secrets requeridos](#4-secrets-requeridos)
5. [Configuración inicial del VPS](#5-configuración-inicial-del-vps)
6. [Disparar un despliegue](#6-disparar-un-despliegue)
7. [Migrar a otro repositorio](#7-migrar-a-otro-repositorio)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Resumen

| Atributo | Valor |
|---|---|
| Plataforma CI/CD | GitHub Actions |
| Runner | `ubuntu-latest` (gestionado por GitHub) |
| Workflow | `.github/workflows/deploy.yml` |
| Trigger automático | `push` a la rama `main` |
| Trigger manual | "Run workflow" desde la pestaña Actions de GitHub |
| Estrategia | Build en runner + ejecución remota de `deploy.sh` vía SSH |
| Concurrency | Solo un deploy a la vez; cancela el anterior si llega uno nuevo |
| Repositorio actual | `https://github.com/jaiverramosweb/sistema_inventario.git` |
| Acción SSH usada | `appleboy/ssh-action@v1.0.3` |

---

## 2. Arquitectura del pipeline

```
┌─────────────────┐
│ Developer push  │
│   a main        │
└────────┬────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  GitHub Actions Runner (ubuntu-latest)│
│                                      │
│  1. Checkout                         │
│  2. Setup PHP 8.2 + extensiones      │
│  3. Setup pnpm 9 + Node 20           │
│  4. composer install + php artisan test
│  5. pnpm install + pnpm run build    │
└────────────┬─────────────────────────┘
             │
             │ SSH (puerto 5922 default)
             ▼
┌──────────────────────────────────────┐
│  VPS de producción                   │
│                                      │
│  /var/www/sistema_inventario/        │
│   ├── temp_deploy/   (git clone)     │
│   │   └─→ git fetch + reset --hard   │
│   │   └─→ ./deploy.sh                │
│   │                                  │
│   ├── releases/                      │
│   │   ├── 20260509120000/  ← nueva   │
│   │   ├── 20260508140000/            │
│   │   └── 20260507100000/            │
│   ├── current → releases/20260509...│
│   └── shared/  (.env, storage/)      │
└──────────────────────────────────────┘
```

> **Nota importante**: las pruebas (`php artisan test`) se ejecutan en el runner de GitHub Actions **antes** del deploy. Si fallan, el deploy NO se ejecuta. Esto protege producción.

---

## 3. El workflow actual paso a paso

Archivo: `.github/workflows/deploy.yml`

### 3.1 Triggers

```yaml
on:
  push:
    branches: [ main ]
  workflow_dispatch:
```

- **`push` a `main`**: cualquier commit que llegue a la rama `main` dispara el deploy
- **`workflow_dispatch`**: permite disparar el workflow manualmente desde la UI de GitHub (útil para re-deploys o testing)

### 3.2 Concurrency

```yaml
concurrency:
  group: deploy-main
  cancel-in-progress: true
```

Si ya hay un deploy ejecutándose y llega uno nuevo, el anterior se **cancela**. Garantiza que no haya dos deploys simultáneos pisándose en el VPS.

### 3.3 Build en el runner

```yaml
- uses: actions/checkout@v4
- uses: shivammathur/setup-php@v2
  with:
    php-version: '8.2'
    extensions: mbstring, xml, ctype, iconv, pgsql, pdo_pgsql, zip
- uses: pnpm/action-setup@v3
  with: { version: 9, run_install: false }
- uses: actions/setup-node@v4
  with:
    node-version: '20'
    cache: 'pnpm'
    cache-dependency-path: admin-front/pnpm-lock.yaml
```

- **Checkout**: trae el código a la máquina del runner
- **Setup PHP**: PHP 8.2 con todas las extensiones que el sistema necesita (incluido `pdo_pgsql` para PostgreSQL)
- **Setup pnpm**: pnpm versión 9, sin instalar dependencias todavía
- **Setup Node**: Node 20 con cache automático del lockfile de pnpm — acelera builds posteriores

### 3.4 Validación e instalación

```yaml
- name: Install Backend Dependencies
  run: |
    cd admin-back
    composer install --no-interaction --prefer-dist
    php artisan test

- name: Install Frontend Deps and Build
  run: |
    cd admin-front
    pnpm install --frozen-lockfile
    pnpm run build
```

- **Backend**: instala dependencias y corre la **suite de tests**. Si algún test falla, el workflow falla y NO se ejecuta el deploy.
- **Frontend**: instala con `--frozen-lockfile` (no permite cambios al lockfile durante el build) y ejecuta `pnpm run build`.

> El `dist/` generado en este paso **no se usa** para el deploy directo — el deploy real reconstruye en el VPS. Este build local sirve como **validación** de que el código compila.

### 3.5 Deploy remoto vía SSH

```yaml
- name: Deploy to VPS via SSH
  uses: appleboy/ssh-action@v1.0.3
  with:
    host: ${{ secrets.REMOTE_HOST }}
    username: ${{ secrets.REMOTE_USER }}
    key: ${{ secrets.SSH_PRIVATE_KEY }}
    port: ${{ secrets.REMOTE_PORT || 5922 }}
    command_timeout: 40m
    script: |
      if [ ! -d "/var/www/sistema_inventario/temp_deploy/.git" ]; then
          rm -rf /var/www/sistema_inventario/temp_deploy
          git clone -b main https://github.com/jaiverramosweb/sistema_inventario.git \
              /var/www/sistema_inventario/temp_deploy
      fi

      cd /var/www/sistema_inventario/temp_deploy
      git fetch --prune origin main
      git checkout main
      git reset --hard origin/main
      git clean -fdx
      git rev-parse --short HEAD

      chmod +x deploy.sh
      ./deploy.sh
```

**Qué hace en el VPS:**

1. **Si no existe `temp_deploy/.git`**: clona el repositorio desde cero
2. **Si ya existe**: hace `git fetch` + `git reset --hard origin/main` + `git clean -fdx` para forzar un estado idéntico a `origin/main` (descarta cualquier cambio local)
3. **Imprime el hash corto del HEAD** para auditoría
4. **Ejecuta `deploy.sh`**, que se encarga del deploy blue-green con releases versionadas (ver `docs/INSTALACION.md §6`)

**Timeout**: 40 minutos. Suficiente para builds + migraciones grandes.

---

## 4. Secrets requeridos

Los secrets se configuran en GitHub: **Settings → Secrets and variables → Actions → New repository secret**.

| Secret | Descripción | Ejemplo |
|---|---|---|
| `REMOTE_HOST` | IP o hostname del VPS | `203.0.113.45` o `vps.empresa.com` |
| `REMOTE_USER` | Usuario SSH del VPS (con permisos sudo) | `Sitecsas` |
| `SSH_PRIVATE_KEY` | Clave privada SSH completa (incluyendo `-----BEGIN ... -----END`) | Ver §5.2 |
| `REMOTE_PORT` | Puerto SSH (opcional). Si no se setea, usa **5922** | `5922` o `22` |

> **`REMOTE_PORT` con default 5922**: el operador `${{ secrets.REMOTE_PORT || 5922 }}` significa que si no configurás el secret, se usa el puerto 5922 (no el 22 estándar). Esto es una práctica de hardening: cambiar el puerto SSH default reduce ruido de bots.

### 4.1 Cómo configurar un secret

1. Ir a tu repositorio en GitHub
2. **Settings** (pestaña superior derecha)
3. En el menú lateral: **Secrets and variables → Actions**
4. Botón **New repository secret**
5. Nombre: el del cuadro de arriba (ej. `REMOTE_HOST`)
6. Valor: el contenido (sin comillas)
7. **Add secret**

> Los secrets están **cifrados** y no se pueden leer una vez guardados, solo sobrescribir. Se exponen al workflow como variables de entorno solo durante la ejecución.

---

## 5. Configuración inicial del VPS

Antes de que el primer deploy funcione, el VPS debe estar preparado.

### 5.1 Pre-requisitos en el VPS

- Ubuntu 22.04+ (o equivalente)
- Usuario dedicado con permisos sudo (ej. `Sitecsas`) y `www-data` como grupo secundario
- **PHP 8.2-FPM** + extensiones (`pdo_pgsql`, `mbstring`, `gd`, `zip`, etc.)
- **PostgreSQL 14+** con base de datos y usuario creados
- **Composer 2.x** y **Node 20+** + **pnpm 9** instalados globalmente
- **Nginx** configurado apuntando a `/var/www/sistema_inventario/current/admin-back/public`
- **Git** instalado
- Estructura inicial:
  ```
  /var/www/sistema_inventario/
  ├── shared/
  │   ├── .env             ← config de producción del back
  │   └── storage/         ← storage compartido
  ├── releases/            ← se crean al hacer deploy
  └── temp_deploy/         ← se crea automáticamente en el primer deploy
  ```
- (Opcional) Servicio systemd `inventario-queue.service` si hay colas

### 5.2 Generar el par de claves SSH para Actions

En tu máquina local (NO en el VPS):

```bash
# Genera un par de claves dedicado para CI/CD (no reuses tu clave personal)
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/inventario_deploy

# Esto genera dos archivos:
#  ~/.ssh/inventario_deploy       → clave privada (va al secret SSH_PRIVATE_KEY)
#  ~/.ssh/inventario_deploy.pub   → clave pública (va al VPS)
```

### 5.3 Autorizar la clave pública en el VPS

En el VPS, agregá la clave pública al `authorized_keys` del usuario que va a hacer deploy:

```bash
# Conectado al VPS como Sitecsas
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "<contenido de inventario_deploy.pub>" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 5.4 Cargar la clave privada como secret

En GitHub: **Settings → Secrets → Actions → New secret**

- Nombre: `SSH_PRIVATE_KEY`
- Valor: el contenido COMPLETO de `~/.ssh/inventario_deploy` (incluyendo las líneas `-----BEGIN OPENSSH PRIVATE KEY-----` y `-----END OPENSSH PRIVATE KEY-----`)

### 5.5 Probar conexión SSH

Antes de disparar el primer workflow, verificá que la conexión funciona:

```bash
ssh -i ~/.ssh/inventario_deploy -p 5922 Sitecsas@<IP_VPS>
```

Si entra sin pedir password, la conexión está lista.

### 5.6 Pre-cargar `.env` de producción

El primer deploy fallará si no existe `/var/www/sistema_inventario/shared/.env`. Crearlo manualmente con todas las variables de producción (ver `docs/CONFIGURACION.md`).

---

## 6. Disparar un despliegue

### 6.1 Automático

Cualquier `push` o merge a `main` dispara el workflow automáticamente.

```bash
git checkout main
git merge feature/mi-cambio
git push origin main
# El workflow se dispara solo
```

Seguir el progreso en GitHub: pestaña **Actions** del repositorio.

### 6.2 Manual

Útil para re-deploys sin código nuevo (ej. después de un rollback o un cambio en `.env`):

1. Ir a la pestaña **Actions** del repositorio
2. Seleccionar el workflow **"Deploy to VPS"** en el menú lateral
3. Botón **Run workflow** (desplegable arriba a la derecha)
4. Branch: `main`
5. Botón verde **Run workflow**

### 6.3 Monitorear el deploy

Durante el deploy podés ver logs en tiempo real:

1. **Actions** → último run
2. Click en el job **"deploy"**
3. Expandir el step **"Deploy to VPS via SSH"**

El último mensaje exitoso debería ser algo como:

```
✅ Despliegue completado con éxito.
```

(Viene del `echo` final de `deploy.sh`).

---

## 7. Migrar a otro repositorio

Si necesitás clonar este proyecto a otro repositorio de GitHub (ej. fork de cliente, proyecto derivado, cambio de organización), seguí estos pasos para que el workflow siga funcionando.

### 7.1 Resumen de qué hay que cambiar

El workflow tiene **una URL hardcoded** que debe actualizarse:

```yaml
# .github/workflows/deploy.yml — línea 63
git clone -b main https://github.com/jaiverramosweb/sistema_inventario.git \
    /var/www/sistema_inventario/temp_deploy
```

Hay que cambiar `jaiverramosweb/sistema_inventario` por el slug del nuevo repo (ej. `mi-organizacion/inventario-pro`).

### 7.2 Procedimiento completo

#### Paso 1 — Crear el nuevo repositorio en GitHub

En la organización destino:

1. **New repository** → nombre (ej. `inventario-cliente`)
2. **Privado** (recomendado, contiene scripts y configs sensibles)
3. **NO** inicializar con README ni .gitignore (vamos a importar el repo existente)

#### Paso 2 — Mover el código al nuevo repositorio

Hay dos opciones:

**Opción A — Push del repo actual con todo su historial (recomendado)**

```bash
cd /c/xampp/htdocs/proyecto_inventario

# Cambiar el remote
git remote remove origin
git remote add origin https://github.com/<NUEVA_ORG>/<NUEVO_REPO>.git

# Push de todas las ramas y tags
git push -u origin --all
git push origin --tags
```

**Opción B — Clone fresco sin historial**

```bash
# En una carpeta nueva
git clone https://github.com/jaiverramosweb/sistema_inventario.git inventario-nuevo
cd inventario-nuevo
rm -rf .git
git init
git add .
git commit -m "feat: importación inicial desde sistema_inventario"
git branch -M main
git remote add origin https://github.com/<NUEVA_ORG>/<NUEVO_REPO>.git
git push -u origin main
```

#### Paso 3 — Actualizar la URL hardcoded del workflow

Editar `.github/workflows/deploy.yml` línea 63:

```diff
- git clone -b main https://github.com/jaiverramosweb/sistema_inventario.git \
+ git clone -b main https://github.com/<NUEVA_ORG>/<NUEVO_REPO>.git \
      /var/www/sistema_inventario/temp_deploy
```

> Si el repo nuevo es **privado**, el `git clone` necesita autenticación. Ver §7.5 abajo.

Commitear:

```bash
git add .github/workflows/deploy.yml
git commit -m "ci: actualizar URL del repositorio en deploy workflow"
git push origin main
```

#### Paso 4 — Configurar los Secrets en el nuevo repositorio

Los secrets **NO se transfieren** automáticamente entre repositorios. Hay que configurarlos manualmente:

1. Ir al **nuevo repositorio** → **Settings → Secrets and variables → Actions**
2. Crear los 4 secrets:
   - `REMOTE_HOST`
   - `REMOTE_USER`
   - `SSH_PRIVATE_KEY`
   - `REMOTE_PORT` (opcional)

> Si el VPS sigue siendo el mismo, los valores son los mismos. Si cambiás de VPS, ver §5 para regenerar claves y reconfigurar.

#### Paso 5 — Limpiar `temp_deploy` en el VPS

Si el VPS sigue siendo el mismo y `temp_deploy` apunta al repo viejo, hay que limpiarlo para que el primer deploy clone el repo nuevo:

```bash
# Conectado al VPS
sudo rm -rf /var/www/sistema_inventario/temp_deploy
```

En el siguiente deploy, el workflow detectará que no existe y lo clonará desde la URL nueva.

#### Paso 6 — Disparar el primer deploy

Push a `main` o **Run workflow** manual. Verificar que:

- El step "Deploy to VPS via SSH" loguea: `📥 Cloning fresh repository...`
- El `git clone` apunta a la URL nueva
- El `deploy.sh` se ejecuta sin errores

### 7.3 Checklist de migración

Para no olvidar nada al cambiar de repo:

- [ ] Repositorio nuevo creado en GitHub (privado)
- [ ] Código pusheado al repo nuevo
- [ ] URL del `git clone` en `deploy.yml` actualizada
- [ ] Commit y push del cambio de URL al repo nuevo
- [ ] Secrets `REMOTE_HOST`, `REMOTE_USER`, `SSH_PRIVATE_KEY`, `REMOTE_PORT` configurados en el repo nuevo
- [ ] `temp_deploy` viejo eliminado del VPS (si reusás VPS)
- [ ] Primer deploy ejecutado exitosamente
- [ ] Branch protection del nuevo repo configurado para `main` (recomendado)
- [ ] Webhooks/integraciones del repo viejo migrados o desactivados

### 7.4 Repositorio antiguo: archivar o eliminar

Una vez confirmado que el nuevo flujo funciona:

- **Archivar** (recomendado): mantiene el historial accesible pero impide nuevos commits — Settings → Archive this repository
- **Eliminar**: destructivo, solo si estás seguro de que nadie lo usa

### 7.5 Caso especial — repositorio privado

Si el nuevo repositorio es **privado**, el `git clone` desde el VPS no funcionará con HTTPS plano. Tres opciones:

**A) Usar `git@github.com:...` con la SSH key del VPS**

1. En el VPS, generar una clave SSH dedicada:
   ```bash
   ssh-keygen -t ed25519 -C "vps-deploy-readonly" -f ~/.ssh/github_deploy
   ```
2. Agregar la clave pública (`github_deploy.pub`) como **Deploy Key** del repo en GitHub:
   - Repositorio → **Settings → Deploy Keys → Add deploy key**
   - Pegar el contenido de `github_deploy.pub`
   - **Allow write access**: NO (solo necesita lectura)
3. Cambiar la URL en `deploy.yml`:
   ```yaml
   git clone -b main git@github.com:<ORG>/<REPO>.git /var/www/sistema_inventario/temp_deploy
   ```
4. Asegurar que el VPS confíe en GitHub:
   ```bash
   ssh-keyscan github.com >> ~/.ssh/known_hosts
   ```

**B) Usar Personal Access Token (PAT)**

Menos seguro pero más simple:

1. En GitHub: **Settings → Developer settings → Personal access tokens → Fine-grained tokens**
2. Crear un token con scope **"Contents: Read"** sobre el repositorio
3. Agregar como secret `GH_PAT` en el repo
4. Cambiar la URL del clone para usar el token (ojo: pasarlo desde el workflow al VPS):
   ```yaml
   script: |
     git clone -b main https://x-access-token:${{ secrets.GH_PAT }}@github.com/<ORG>/<REPO>.git temp_deploy
   ```

**C) Reemplazar el flujo "clone en VPS" por "rsync desde el runner"**

Cambiar el approach: subir los archivos compilados desde el runner al VPS con `rsync` o `scp`, sin que el VPS necesite clonar. Esto cambia bastante el `deploy.yml` — fuera de alcance de esta guía.

---

## 8. Troubleshooting

### 8.1 El workflow falla en "Install Backend Dependencies"

**Causa común**: tests que no pasan.

**Diagnóstico**: expandir el step en GitHub Actions y leer el output de `php artisan test`.

**Solución**: corregir el test que falla antes de mergear a `main`. Si el test es flaky, marcarlo como skipped temporalmente con justificación.

### 8.2 El workflow falla en "Install Frontend Deps and Build"

**Causa común**: error de TypeScript, ESLint, o dependencia inconsistente con el lockfile.

**Diagnóstico**: leer el output de `pnpm install --frozen-lockfile` y `pnpm run build`.

**Solución**:
- Si es lockfile: regenerar local con `pnpm install`, commitear el lockfile actualizado
- Si es build: corregir el error y volver a pushear

### 8.3 El step de SSH falla con "ssh: handshake failed"

**Causas posibles**:

| Causa | Verificación |
|---|---|
| `REMOTE_HOST` mal configurado | Probar `ping <IP>` desde local |
| `REMOTE_PORT` incorrecto | Confirmar puerto SSH del VPS (`/etc/ssh/sshd_config`) |
| `SSH_PRIVATE_KEY` mal pegada | Re-pegar incluyendo `BEGIN`/`END` y newline final |
| Clave pública no autorizada en VPS | Verificar `~/.ssh/authorized_keys` del usuario en el VPS |
| Firewall bloqueando GitHub Actions | Permitir puerto SSH desde rangos de IP de GitHub o usar IP whitelist |

**Test manual**: copiar la clave privada del secret a un archivo local y probar:

```bash
ssh -i archivo_clave -p 5922 <REMOTE_USER>@<REMOTE_HOST>
```

### 8.4 El deploy llega al VPS pero `deploy.sh` falla

**Diagnóstico**: leer el output del paso SSH en GitHub Actions. Errores comunes:

| Error | Solución |
|---|---|
| `php artisan migrate --force` falla | Revisar `.env` de producción, credenciales DB |
| `composer install` falla | Memoria insuficiente; aumentar swap o RAM del VPS |
| `pnpm run build` falla en VPS | Ejecutar manualmente para ver error completo |
| Permission denied al copiar archivos | Revisar ownership de `/var/www/sistema_inventario` |

**Solución general**: SSH manual al VPS, ir a `temp_deploy/` y ejecutar `./deploy.sh` paso a paso para identificar el comando que falla.

### 8.5 El workflow se ejecuta pero el sitio no actualiza

**Causa común**: el deploy completó pero PHP-FPM no recargó OPcache.

**Diagnóstico en VPS**:

```bash
ls -la /var/www/sistema_inventario/current  # ¿apunta al release nuevo?
sudo systemctl status php8.2-fpm            # ¿está corriendo?
```

**Solución**:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
```

### 8.6 Concurrency cancela mi deploy importante

Si pusheás dos veces seguidas, el primer deploy se cancela. Si el cambio es crítico:

1. Esperar a que termine el primer deploy
2. O eliminar la sección `concurrency` del workflow (no recomendado)

### 8.7 Necesito hacer rollback urgente

GitHub Actions NO ofrece rollback nativo. Pero el `deploy.sh` mantiene las últimas 3 releases en `/var/www/sistema_inventario/releases/`. Para rollback inmediato:

```bash
# SSH al VPS
cd /var/www/sistema_inventario
ls -t releases/ | head -5            # ver releases disponibles
sudo rm current
sudo ln -s "$PWD/releases/<release_anterior>" current
sudo systemctl restart php8.2-fpm
```

Detalle completo en `docs/OPERACIONES.md §6.2`.

---

## Referencias

- Workflow real: `.github/workflows/deploy.yml`
- Script remoto: `deploy.sh` (raíz del repo)
- [Manual de instalación — sección deploy](./INSTALACION.md#6-despliegue-en-producción-deploysh)
- [Manual de operaciones — rollback](./OPERACIONES.md#6-despliegue-y-rollback)
- [Documentación oficial appleboy/ssh-action](https://github.com/appleboy/ssh-action)
- [GitHub Actions — Encrypted secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

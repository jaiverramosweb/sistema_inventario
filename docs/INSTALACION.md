# Manual de Instalación

> **Sistema de Inventario Pro** — Guía completa de instalación en entornos de desarrollo y producción.

---

## Tabla de contenidos

1. [Requisitos previos](#1-requisitos-previos)
2. [Estructura del repositorio](#2-estructura-del-repositorio)
3. [Instalación del Backend (Laravel)](#3-instalación-del-backend-laravel)
4. [Instalación del Frontend (Vue 3)](#4-instalación-del-frontend-vue-3)
5. [Instalación con Docker (frontend)](#5-instalación-con-docker-frontend)
6. [Despliegue en producción (deploy.sh)](#6-despliegue-en-producción-deploysh)
7. [Verificación post-instalación](#7-verificación-post-instalación)
8. [Solución de problemas](#8-solución-de-problemas)

---

## 1. Requisitos previos

### 1.1 Software base

| Componente | Versión mínima | Versión recomendada | Comentario |
|---|---|---|---|
| PHP | 8.2 | 8.2.x | Con extensiones: `mbstring`, `pdo_pgsql`, `pgsql`, `openssl`, `bcmath`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`, `curl` |
| Composer | 2.5 | 2.x última | Gestor de dependencias PHP |
| Node.js | 18 LTS | 20 LTS | Para construir el frontend |
| pnpm | 8 | 9.0.6 | Gestor de paquetes preferido (también funciona npm/yarn) |
| PostgreSQL | 14 | 15+ | Motor de base de datos |
| Servidor Web | Apache 2.4 / Nginx 1.22 | Nginx 1.24 | En producción se usa Nginx |
| Git | 2.30 | 2.40+ | Para clonar el repositorio |

### 1.2 Requisitos de hardware (referencia)

| Entorno | CPU | RAM | Disco |
|---|---|---|---|
| Desarrollo | 2 vCPU | 4 GB | 10 GB libres |
| Producción (50 usuarios) | 2 vCPU | 4 GB | 30 GB SSD |
| Producción (200 usuarios) | 4 vCPU | 8 GB | 60 GB SSD |

### 1.3 Verificación de versiones

```bash
php -v                  # PHP 8.2.x
composer -V             # Composer 2.x
node -v                 # v18.x.x o v20.x.x
pnpm -v                 # 8.x o 9.x
psql --version          # psql 14.x+
git --version           # 2.30+
```

### 1.4 Extensiones PHP requeridas

Verificar que estén habilitadas:

```bash
php -m | grep -E "mbstring|pdo_pgsql|pgsql|openssl|bcmath|tokenizer|xml|ctype|json|fileinfo|gd|zip|curl"
```

En **Windows con XAMPP** las extensiones se habilitan en `C:\xampp\php\php.ini` quitando el `;` delante de cada `extension=...`.

---

## 2. Estructura del repositorio

```
proyecto_inventario/
├── admin-back/                 # API REST — Laravel 12 + PHP 8.2
├── admin-front/                # SPA — Vue 3 + Vuetify 3
├── docs/                       # Documentación técnica (este directorio)
├── entrega_cliente/            # Documentos Word para entrega final
├── deploy.sh                   # Script de despliegue blue-green a producción
└── README.md                   # Visión general del proyecto
```

El backend (`admin-back/`) y el frontend (`admin-front/`) son **dos aplicaciones desacopladas** que se instalan por separado.

---

## 3. Instalación del Backend (Laravel)

### 3.1 Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO> proyecto_inventario
cd proyecto_inventario
```

### 3.2 Crear la base de datos PostgreSQL

```bash
# Conectarse como superusuario postgres
psql -U postgres

# Dentro de psql:
CREATE DATABASE sistema_inventario WITH ENCODING 'UTF8';
CREATE USER inventario_user WITH ENCRYPTED PASSWORD 'TU_PASSWORD_SEGURO';
GRANT ALL PRIVILEGES ON DATABASE sistema_inventario TO inventario_user;
\q
```

> **Nota**: el nombre de la base, el usuario y la contraseña son a tu criterio. Anotalos: se usan en el `.env` del paso siguiente.

### 3.3 Instalar dependencias de PHP

```bash
cd admin-back
composer install
```

En producción:

```bash
composer install --no-dev --optimize-autoloader --prefer-dist
```

### 3.4 Configurar variables de entorno

```bash
cp .env.example .env        # En desarrollo
# o
cp .env.production.example .env   # En producción
```

Editar `.env` con las credenciales reales (ver detalle en [docs/CONFIGURACION.md](./CONFIGURACION.md)). Como mínimo, configurar:

```env
APP_NAME="Sistema de Inventario"
APP_ENV=production              # o local en dev
APP_DEBUG=false                 # true solo en dev
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sistema_inventario
DB_USERNAME=inventario_user
DB_PASSWORD=TU_PASSWORD_SEGURO
```

### 3.5 Generar claves de aplicación y JWT

```bash
php artisan key:generate            # Genera APP_KEY
php artisan jwt:secret              # Genera JWT_SECRET en .env
```

> **Importante**: ambos secretos NUNCA deben subirse al repositorio ni compartirse por canales inseguros. Si se comprometen, regenerarlos invalida todas las sesiones activas.

### 3.6 Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Esto:
- Crea las ~60 tablas del sistema (productos, almacenes, ventas, compras, etc.)
- Ejecuta los seeders:
  - `SucursalSeeder` — sucursal inicial "Colombia"
  - `PermissionsDemoSeeder` — 41 permisos del sistema, rol **Super-Admin** y usuario administrador
  - `PipelineStageSeeder` — etapas del CRM

> **Producción**: revisar `database/seeders/PermissionsDemoSeeder.php` antes de ejecutar y cambiar la contraseña del usuario superadmin demo, o crear un seeder dedicado de producción.

### 3.7 Permisos sobre directorios (Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache    # Si Apache/Nginx corre como www-data
```

En **Windows con XAMPP** no es necesario configurar permisos.

### 3.8 Levantar el servidor de desarrollo

```bash
php artisan serve
# Servidor: http://127.0.0.1:8000
```

Para producción usar Nginx/Apache apuntando al directorio `admin-back/public/` como document root.

---

## 4. Instalación del Frontend (Vue 3)

### 4.1 Instalar dependencias

```bash
cd admin-front
pnpm install
```

> El `postinstall` ejecuta automáticamente `build:icons` (genera el bundle de Iconify) y `msw:init` (inicializa Mock Service Worker). Si fallan, se pueden ejecutar manualmente:
> ```bash
> pnpm run build:icons
> pnpm run msw:init
> ```

### 4.2 Configurar variables de entorno

Crear `admin-front/.env` con:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

En producción usar la URL pública del backend (ver `.env.production.example`).

### 4.3 Levantar el servidor de desarrollo

```bash
pnpm run dev
# Servidor: http://localhost:5173
```

Vite ofrece HMR (recarga en caliente). Cualquier cambio en `src/` se refleja en segundos.

### 4.4 Compilar para producción

```bash
pnpm run build
# Salida: admin-front/dist/
```

El contenido de `dist/` es lo que se sirve estáticamente con Nginx/Apache en producción.

### 4.5 Previsualizar el build local

```bash
pnpm run preview
# Servidor: http://localhost:5050
```

---

## 5. Instalación con Docker (frontend)

El frontend incluye configuración Docker para entornos consistentes.

### 5.1 Desarrollo (HMR)

```bash
cd admin-front
docker compose -f docker-compose.dev.yml up --build
# Servidor: http://localhost:5173
```

`dev.Dockerfile` instala dependencias y arranca `pnpm dev --host` con volúmenes montados para hot-reload.

### 5.2 Producción (Nginx + build estático)

```bash
cd admin-front
docker compose -f docker-compose.prod.yml up --build -d
# Servidor: http://localhost:8080
```

`prod.Dockerfile` es multi-stage:
1. Etapa `builder` (Node 18) — instala y ejecuta `pnpm run build`
2. Etapa final (Nginx Alpine) — copia `dist/` a `/usr/share/nginx/html`

`nginx.conf` aplica el patrón SPA: cualquier ruta no encontrada cae en `index.html` para que Vue Router maneje el ruteo.

> **Backend en Docker**: actualmente el back NO tiene Dockerfile dedicado. Se ejecuta nativo (PHP-FPM + Nginx). Si se requiere dockerizar el back, se debe crear `admin-back/Dockerfile` con PHP 8.2-fpm, extensiones, Composer y entrypoint.

---

## 6. Despliegue en producción (deploy.sh)

El proyecto incluye `deploy.sh` para despliegue blue-green con releases versionados.

> **Para despliegue automatizado desde GitHub** (push a `main` → deploy automático al VPS), ver el documento dedicado **[docs/CICD.md](./CICD.md)**. La siguiente sección describe el script `deploy.sh` que se ejecuta en el VPS — este script es invocado por GitHub Actions, pero también puede ejecutarse manualmente.

### 6.1 Estructura esperada en el servidor

```
/var/www/sistema_inventario/
├── current -> releases/20260509143025/    # Symlink a release activa
├── releases/
│   ├── 20260508120000/                    # Release anterior (backup)
│   ├── 20260509143025/                    # Release activa
│   └── 20260509150030/                    # Nueva release
└── shared/
    ├── .env                                # Variables de entorno persistentes
    └── storage/                            # Storage de Laravel (compartido entre releases)
        ├── app/
        ├── framework/
        └── logs/
```

### 6.2 Flujo del script

1. Crea una nueva carpeta `releases/<timestamp>/`
2. Copia el código del directorio actual a la release nueva
3. Enlaza simbólicamente `.env` y `storage/` desde `shared/`
4. Instala dependencias del back con `composer install --no-dev --optimize-autoloader`
5. Ejecuta `php artisan migrate --force`
6. Cachea config, rutas, vistas y eventos
7. Reinicia el worker de colas si existe el servicio systemd
8. Instala dependencias del front con `pnpm install --frozen-lockfile`
9. Compila el front con `pnpm run build`
10. Cambia el symlink `current` a la nueva release
11. Reinicia PHP-FPM y recarga Nginx
12. Limpia releases antiguas (mantiene las últimas 3)
13. Aplica permisos correctos al usuario `Sitecsas:www-data`

### 6.3 Ejecución

Desde el servidor de producción, en el directorio donde GitHub Actions (o el flujo manual) deja el código nuevo:

```bash
chmod +x deploy.sh
./deploy.sh
```

> **Pre-requisitos del servidor**:
> - PHP 8.2-FPM activo (`systemctl status php8.2-fpm`)
> - Nginx configurado apuntando a `/var/www/sistema_inventario/current/admin-back/public`
> - PostgreSQL 14+ con la base de datos creada
> - Usuario del sistema `Sitecsas` y grupo `www-data` existentes
> - `/var/www/sistema_inventario/shared/.env` configurado a mano la primera vez
> - (Opcional) Servicio systemd `inventario-queue.service` para colas

### 6.4 Configuración de Nginx (referencia)

```nginx
server {
    listen 443 ssl http2;
    server_name api.sistema-inventario.com;

    ssl_certificate     /etc/ssl/certs/inventario.crt;
    ssl_certificate_key /etc/ssl/private/inventario.key;

    root /var/www/sistema_inventario/current/admin-back/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    client_max_body_size 50M;
}

# Frontend (SPA) — servidor estático
server {
    listen 443 ssl http2;
    server_name app.sistema-inventario.com;

    ssl_certificate     /etc/ssl/certs/inventario.crt;
    ssl_certificate_key /etc/ssl/private/inventario.key;

    root /var/www/sistema_inventario/current/admin-front/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    gzip on;
    gzip_types text/css application/javascript image/svg+xml;
}
```

---

## 7. Verificación post-instalación

### 7.1 Backend

```bash
# 1. Servidor responde
curl http://127.0.0.1:8000

# 2. Endpoint de login responde
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@sitecsas.com","password":"<PASSWORD>"}'

# Debe devolver un JSON con `access_token`.
```

### 7.2 Frontend

1. Abrir `http://localhost:5173`
2. Verificar que carga la pantalla de login
3. Iniciar sesión con `superadmin@sitecsas.com`
4. Confirmar que el dashboard muestra los KPIs
5. Navegar a Productos, Ventas, Configuración → todas deben cargar

### 7.3 Tests E2E (smoke)

```bash
cd admin-front
pnpm run e2e:install            # primera vez
PW_E2E_PASSWORD="<PASSWORD>" pnpm run e2e:smoke
```

Debe pasar la suite "Smoke E2E - módulos críticos".

---

## 8. Solución de problemas

### 8.1 Error: "could not find driver" al migrar

**Causa**: extensión `pdo_pgsql` no habilitada.
**Solución**: descomentar `extension=pdo_pgsql` en `php.ini` y reiniciar PHP-FPM/Apache.

### 8.2 Error 401 en cualquier endpoint

**Causa**: token JWT expirado, malformado o `JWT_SECRET` inconsistente.
**Solución**:
1. Verificar que `JWT_SECRET` esté seteado: `php artisan jwt:secret --show`
2. Hacer logout en el frontend y volver a iniciar sesión
3. Si persiste, regenerar el secret: `php artisan jwt:secret` (invalida todas las sesiones)

### 8.3 Error 419 (CSRF token mismatch)

**Causa**: rutas API que esperan sesión web; **no debería ocurrir** porque la API usa JWT, no sesión.
**Solución**: confirmar que la ruta está en `routes/api.php` y no en `routes/web.php`.

### 8.4 El frontend no se conecta a la API (CORS / Mixed Content)

**Causa**: `VITE_API_BASE_URL` apunta a una URL incorrecta o protocolo distinto (HTTP vs HTTPS).
**Solución**:
1. Confirmar `VITE_API_BASE_URL` en `admin-front/.env`
2. Reconstruir: `pnpm run build`
3. Si aparece error CORS en consola, configurar `config/cors.php` en el back con el dominio del front

### 8.5 `php artisan migrate` falla con "table already exists"

**Causa**: la base ya tiene tablas de un intento previo.
**Solución (DEV)**: `php artisan migrate:fresh --seed` (BORRA TODOS LOS DATOS).
**Solución (PROD)**: revisar manualmente, hacer backup, y aplicar solo las migraciones pendientes con `php artisan migrate --pretend` para inspeccionar.

### 8.6 `pnpm install` falla por permisos en Windows

**Causa**: bloqueo de archivos por antivirus o Defender.
**Solución**: ejecutar PowerShell como administrador o agregar exclusión a la carpeta del proyecto.

### 8.7 `php artisan jwt:secret` no genera nada

**Causa**: el paquete `tymon/jwt-auth` o `php-open-source-saver/jwt-auth` no está instalado.
**Solución**:
```bash
composer require php-open-source-saver/jwt-auth
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 8.8 El usuario superadmin no puede iniciar sesión

**Causa**: el seeder no se ejecutó o la contraseña fue cambiada.
**Solución**:
```bash
php artisan db:seed --class=PermissionsDemoSeeder
```

O resetear la contraseña directamente:
```bash
php artisan tinker
>>> $u = App\Models\User::where('email','superadmin@sitecsas.com')->first();
>>> $u->password = bcrypt('NUEVA_PASSWORD');
>>> $u->save();
```

### 8.9 `deploy.sh` falla en `php artisan migrate --force`

**Causa**: el `.env` de producción apunta a una BD no accesible o las credenciales son incorrectas.
**Solución**: verificar `/var/www/sistema_inventario/shared/.env`, especialmente `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

---

## Referencias

- [Configuración detallada de variables](./CONFIGURACION.md)
- [Modelo de base de datos](./MODELO_BD.md)
- [Arquitectura del sistema](./ARQUITECTURA.md)
- [Manual de operaciones (backups, logs, monitoreo)](./OPERACIONES.md)
- [Hardening y seguridad](./SEGURIDAD.md)

---

*Documento mantenido en el repositorio. Última actualización: 2026-05-09.*

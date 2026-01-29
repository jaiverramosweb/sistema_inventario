#!/bin/bash
set -e

# Configuración
APP_DIR="/var/www/sistema_inventario"
RELEASE_DIR="$APP_DIR/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="$APP_DIR/shared"

echo "🚀 Iniciando despliegue..."

# 1. Crear nueva carpeta de release
mkdir -p "$RELEASE_DIR"

# 2. Copiar código desde la carpeta temporal (donde GitHub Actions sube el código)
cp -R . "$RELEASE_DIR"

# 3. Enlace simbólico de archivos compartidos
ln -nfs "$SHARED_DIR/.env" "$RELEASE_DIR/admin-back/.env"

# Asegurar que existan los directorios de storage en el directorio compartido
mkdir -p "$SHARED_DIR/storage/framework/cache"
mkdir -p "$SHARED_DIR/storage/framework/sessions"
mkdir -p "$SHARED_DIR/storage/framework/views"
mkdir -p "$SHARED_DIR/storage/app/public"
mkdir -p "$SHARED_DIR/storage/logs"

rm -rf "$RELEASE_DIR/admin-back/storage"
ln -nfs "$SHARED_DIR/storage" "$RELEASE_DIR/admin-back/storage"

# 4. Backend: Instalar dependencias y optimizar
cd "$RELEASE_DIR/admin-back"
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Frontend: Instalar dependencias y compilar
cd "$RELEASE_DIR/admin-front"
pnpm install
pnpm run build

# 6. Cambiar enlace simbólico 'current'
ln -nfs "$RELEASE_DIR" "$APP_DIR/current"

# 7. Reiniciar PHP-FPM para limpiar OPcache
sudo systemctl restart php8.2-fpm

# 8. Limpiar releases antiguos (mantener últimos 3)
ls -dt $APP_DIR/releases/* | tail -n +4 | xargs rm -rf

echo "✅ Despliegue completado con éxito."

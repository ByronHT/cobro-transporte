#!/bin/bash
set -e

echo "🚀 Iniciando aplicación Laravel en Railway..."

# Esperar a que la base de datos esté lista
echo "⏳ Esperando conexión a la base de datos..."
php artisan db:show || echo "⚠️ No se pudo conectar a la BD todavía, continuando..."

# Limpiar cachés de Laravel
echo "🧹 Limpiando cachés..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cachear configuración para producción
echo "📦 Cacheando configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones (solo si es necesario)
echo "🔄 Verificando migraciones..."
php artisan migrate --force --no-interaction || echo "⚠️ Error en migraciones, continuando..."

# Crear enlace simbólico para storage
echo "🔗 Creando enlace simbólico para storage..."
php artisan storage:link || echo "ℹ️ El enlace ya existe"

# Asegurarse de que los permisos sean correctos
echo "🔐 Configurando permisos..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Aplicación lista!"
echo "🌐 Servidor iniciando en puerto 80..."

# Ejecutar el comando pasado como argumento (apache2-foreground)
exec "$@"

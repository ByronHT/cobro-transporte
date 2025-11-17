#!/usr/bin/env bash
set -e

echo "Esperando a que la DB esté disponible..."
host="${DB_HOST}"
port="${DB_PORT}"
max_tries=60
i=0
until nc -z "$host" "$port"; do
  i=$((i+1))
  if [ "$i" -ge "$max_tries" ]; then
    echo "No se pudo conectar a la DB después de $max_tries intentos"
    exit 1
  fi
  sleep 1
done
echo "DB accesible"

# Limpiar caches
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true

# Ejecutar migraciones
php artisan migrate --force || { echo "Error en migraciones"; exit 1; }

# Finalmente iniciar Apache
exec apache2-foreground

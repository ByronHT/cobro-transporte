#!/bin/bash

# Script para regenerar index.html de Laravel después de que npm run build lo sobrescriba
# Usar en Railway después de hacer deploy cuando el panel web no funcione

echo "🔧 Regenerando index.html para Laravel..."

# Limpiar caché de Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Ejecutar npm run build para generar assets de React
npm run build

# El postbuild.js ya genera el index.html correcto para React/Capacitor
# Pero Laravel necesita que public/index.html apunte a las rutas de Laravel

# Verificar si existe index.html generado por Vite
if [ -f "public/index.html" ]; then
    echo "⚠️ Encontrado index.html de React/Capacitor"
    echo "🗑️ Eliminando index.html de React (Laravel usa rutas)"
    rm public/index.html
    echo "✅ index.html eliminado - Laravel ahora manejará las rutas"
else
    echo "ℹ️ No se encontró index.html - Laravel manejará las rutas correctamente"
fi

# Opcional: Regenerar assets manifest
php artisan optimize:clear

echo "✅ Laravel restaurado correctamente"
echo "💡 Ahora el panel web debería funcionar en Railway"

# 🔧 FIX Error 419 y Estilos Faltantes en Railway

## Problema 1: Error 419 CSRF Token

### Variables de Entorno a Agregar en Railway:

Ve a Railway → Tu Proyecto → Variables → Agrega estas:

```bash
# Session Configuration
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.railway.app

# Sanctum
SANCTUM_STATEFUL_DOMAINS=cobro-transporte-production.up.railway.app,localhost,127.0.0.1

# App URL (ACTUALIZA CON TU URL REAL)
APP_URL=https://cobro-transporte-production.up.railway.app
```

## Problema 2: Estilos No Cargan

### Solución: Asegurar que Vite compile correctamente

El Dockerfile ya ejecuta `npm run build`, pero necesitas verificar:

### En Railway Shell, ejecuta:

```bash
# 1. Verificar que los assets se compilaron
ls -la public/build

# 2. Limpiar cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 3. Cachear de nuevo
php artisan config:cache
php artisan route:cache
```

## Verificación Rápida

### ¿Los assets existen?

Visita estas URLs en tu navegador:

```
https://cobro-transporte-production.up.railway.app/build/manifest.json
https://cobro-transporte-production.up.railway.app/build/assets/app-[hash].css
https://cobro-transporte-production.up.railway.app/build/assets/app-[hash].js
```

Si responden 404, el build falló.

## Si el problema persiste:

### Opción A: Forzar rebuild en Railway

1. Ve a Railway → Deployments
2. Click en los 3 puntos del último deployment
3. "Redeploy"

### Opción B: Verificar logs de build

1. Railway → Logs
2. Buscar errores en la compilación de npm
3. Si hay errores de memoria, es posible que necesites optimizar el build

## Variables de Entorno COMPLETAS para Railway

```bash
# Application
APP_NAME=Interflow
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:qPmXQYhyMZL3R+lP79RIXkUbNN5C+qQvta17e9MbpQY=
APP_TIMEZONE=America/La_Paz
APP_URL=https://cobro-transporte-production.up.railway.app

# Database (TUS VALORES)
DB_CONNECTION=mysql
DB_HOST=mainline.proxy.rlwy.net
DB_PORT=44459
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=EikcJRVuHWfiEXdewQpuffjuVfsLcoKN

# Session (IMPORTANTE PARA 419)
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.railway.app

# Sanctum (IMPORTANTE PARA 419)
SANCTUM_STATEFUL_DOMAINS=cobro-transporte-production.up.railway.app,localhost,127.0.0.1

# Cache
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

# Broadcasting
BROADCAST_DRIVER=log

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Mail (Opcional)
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@interflow.com
MAIL_FROM_NAME=Interflow
```

## Después de actualizar las variables:

Railway redesplegará automáticamente (2-3 minutos).

Luego prueba de nuevo el login admin.

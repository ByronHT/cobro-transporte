# 🚀 GUÍA DE DESPLIEGUE EN RAILWAY

## Paso 1: Preparar el Proyecto

✅ **Ya completado** - El proyecto está limpio y listo para desplegar.

## Paso 2: Conectar Railway con GitHub

1. Ve a [Railway.app](https://railway.app/)
2. Inicia sesión con tu cuenta de GitHub
3. Click en "New Project"
4. Selecciona "Deploy from GitHub repo"
5. Selecciona el repositorio: `ByronHT/cobro-transporte`

## Paso 3: Configurar Variables de Entorno

En Railway, ve a tu proyecto → Variables → Add variables:

```bash
# Aplicación
APP_NAME="Interflow"
APP_ENV="production"
APP_DEBUG="false"
APP_KEY="base64:qPmXQYhyMZL3R+lP79RIXkUbNN5C+qQvta17e9MbpQY="
APP_TIMEZONE="America/La_Paz"
APP_URL="https://your-project-name.railway.app"

# Base de Datos (Usa los valores de tu MySQL en Railway)
DB_CONNECTION="mysql"
DB_HOST="mainline.proxy.rlwy.net"
DB_PORT="44459"
DB_DATABASE="railway"
DB_USERNAME="root"
DB_PASSWORD="EikcJRVuHWfiEXdewQpuffjuVfsLcoKN"

# Session
SESSION_DRIVER="file"
SESSION_LIFETIME="120"

# Cache
CACHE_DRIVER="file"
FILESYSTEM_DISK="local"

# Queue
QUEUE_CONNECTION="sync"

# Broadcasting
BROADCAST_DRIVER="log"

# Logging
LOG_CHANNEL="stack"
LOG_DEPRECATIONS_CHANNEL="null"
LOG_LEVEL="error"

# Mail (Opcional)
MAIL_MAILER="smtp"
MAIL_HOST=""
MAIL_PORT=""
MAIL_USERNAME=""
MAIL_PASSWORD=""
MAIL_ENCRYPTION=""
MAIL_FROM_ADDRESS="noreply@interflow.com"

# Sanctum
SANCTUM_STATEFUL_DOMAINS="localhost,127.0.0.1,your-project-name.railway.app"

# Redis (Opcional)
REDIS_HOST="127.0.0.1"
REDIS_PASSWORD="null"
REDIS_PORT="6379"
```

## Paso 4: Railway Detectará Automáticamente el Dockerfile

Railway detectará el `Dockerfile` en la raíz del proyecto y lo usará automáticamente.

**El Dockerfile incluye:**
- ✅ PHP 8.2 con Apache
- ✅ Todas las extensiones necesarias
- ✅ Composer y Node.js
- ✅ Build automático de assets con Vite
- ✅ Permisos correctos para Laravel
- ✅ Script de inicio que ejecuta migraciones

## Paso 5: Configurar el Puerto

Railway asignará automáticamente el puerto. El Dockerfile está configurado para usar el puerto 80 internamente, que Railway mapeará correctamente.

## Paso 6: Deploy

1. Railway comenzará a construir automáticamente
2. Verás los logs del build en tiempo real
3. El proceso tomará 5-10 minutos en el primer deploy
4. Una vez completado, Railway te dará una URL pública

## Paso 7: Verificar Deployment

1. Visita tu URL de Railway: `https://your-project-name.railway.app`
2. Deberías ver la página de inicio de tu aplicación
3. Prueba el panel admin: `https://your-project-name.railway.app/admin`
4. Prueba las APIs: `https://your-project-name.railway.app/api/...`

## Problemas Comunes

### Error: "SQLSTATE[HY000] [2002] Connection refused"

**Solución:** Verifica que las variables `DB_HOST`, `DB_PORT`, `DB_PASSWORD` sean correctas.

### Error: "No application encryption key has been specified"

**Solución:** Asegúrate de que `APP_KEY` esté configurado en las variables de entorno.

### Error: Build failed - npm install

**Solución:** El Dockerfile ya usa `--legacy-peer-deps`, pero si falla:
1. Verifica que `package.json` y `package-lock.json` estén en el repo
2. Revisa los logs para ver qué paquete está fallando

### Error: Permisos de storage

**Solución:** El `docker-entrypoint.sh` ya configura los permisos, pero si hay problemas:
- Los directorios `storage/` y `bootstrap/cache/` deben tener permisos 775
- El script de entrypoint los crea y configura automáticamente

## Comandos Útiles

### Ver logs en Railway:
```bash
# Railway CLI (si lo instalaste)
railway logs
```

### Conectar a la base de datos desde local:
```bash
mysql -h mainline.proxy.rlwy.net -P 44459 -u root -p railway
# Password: EikcJRVuHWfiEXdewQpuffjuVfsLcoKN
```

### Ejecutar comandos artisan en Railway:
1. Ve al proyecto en Railway
2. Click en tu servicio web
3. Ve a la pestaña "Shell"
4. Ejecuta comandos artisan:
```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

## Actualizar el Deployment

Cada vez que hagas `git push` a tu repositorio, Railway automáticamente:
1. Detectará los cambios
2. Reconstruirá la imagen Docker
3. Desplegará la nueva versión

## Recursos

- [Railway Documentation](https://docs.railway.app/)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)

## Notas Importantes

1. **Base de Datos:** Railway te da 5GB de almacenamiento gratuito para MySQL
2. **Memoria:** Plan gratuito incluye 512MB RAM, 1GB Disk
3. **Límites:** $5 USD de crédito gratuito mensual
4. **SSL:** Railway proporciona HTTPS automáticamente
5. **Dominio:** Puedes configurar tu propio dominio en Railway → Settings → Domains

---

✅ **Proyecto listo para producción en Railway**

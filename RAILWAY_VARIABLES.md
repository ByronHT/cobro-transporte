# VARIABLES DE ENTORNO PARA RAILWAY

## Copia estas variables en Railway -> Variables -> RAW Editor

```env
APP_DEBUG=false
APP_ENV=production
APP_KEY=base64:qPmXQYhyMZL3R+lP79RIXkUbNN5C+qQvta17e9MbpQY=
APP_NAME=Interflow
APP_TIMEZONE=America/La_Paz
APP_URL=https://cobro-transporte-production-c773.up.railway.app
AWS_ACCESS_KEY_ID=
AWS_BUCKET=
AWS_DEFAULT_REGION=us-east-1
AWS_SECRET_ACCESS_KEY=
AWS_USE_PATH_STYLE_ENDPOINT=false
BROADCAST_DRIVER=log
CACHE_DRIVER=file
DB_CONNECTION=mysql
DB_DATABASE=railway
DB_HOST=yamabiko.proxy.rlwy.net
DB_PASSWORD=bRNdtzEvgeCKDITnwxMBWIrNzdtAfCge
DB_PORT=31069
DB_USERNAME=root
FILESYSTEM_DISK=local
GOOGLE_MAPS_API_KEY=
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=noreply@interflow.com
MAIL_FROM_NAME=Interflow
MAIL_HOST=
MAIL_MAILER=smtp
MAIL_PASSWORD=
MAIL_PORT=
MAIL_USERNAME=
NIXPACKS_BUILD_CMD=composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan db:seed --force && php artisan optimize && chmod -R 775 storage && chmod -R 775 bootstrap/cache && php artisan storage:link
PUSHER_APP_CLUSTER=mt1
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
QUEUE_CONNECTION=sync
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
SANCTUM_STATEFUL_DOMAINS=cobro-transporte-production-c773.up.railway.app,localhost,127.0.0.1
SESSION_DOMAIN=.railway.app
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_APP_KEY=
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
```

---

## ERRORES CORREGIDOS

### 1. APP_KEY vacia
**Problema:** Sin APP_KEY, Laravel no puede encriptar sesiones ni cookies.
**Solucion:** Se agrego una key valida generada con `php artisan key:generate`

### 2. Typo en NIXPACKS_BUILD_CMD
**Problema:** Tenia `boostrap/cache` (falta una 't')
**Solucion:** Corregido a `bootstrap/cache`

### 3. Permisos muy permisivos
**Problema:** `chmod 777` es inseguro
**Solucion:** Cambiado a `chmod -R 775` que es mas seguro

### 4. Faltaba seeder
**Problema:** No se creaba usuario admin automaticamente
**Solucion:** Agregado `php artisan db:seed --force` al build command

### 5. yarn innecesario
**Problema:** El comando tenia `yarn build` pero el proyecto usa npm
**Solucion:** Removido yarn, solo usa npm

---

## COMANDO DE BUILD EXPLICADO

```bash
NIXPACKS_BUILD_CMD=composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan db:seed --force && php artisan optimize && chmod -R 775 storage && chmod -R 775 bootstrap/cache && php artisan storage:link
```

### Desglose:

1. `composer install --no-dev --optimize-autoloader`
   - Instala dependencias PHP sin paquetes de desarrollo
   - Optimiza el autoloader para produccion

2. `npm install`
   - Instala dependencias de Node (React, Vite, etc.)

3. `npm run build`
   - Compila assets con Vite (CSS, JS)

4. `php artisan migrate --force`
   - Ejecuta migraciones de base de datos
   - `--force` es necesario en produccion

5. `php artisan db:seed --force`
   - Ejecuta seeders (crea usuario admin)
   - `--force` es necesario en produccion

6. `php artisan optimize`
   - Cachea configuracion y rutas

7. `chmod -R 775 storage`
   - Da permisos de escritura a storage

8. `chmod -R 775 bootstrap/cache`
   - Da permisos de escritura a cache

9. `php artisan storage:link`
   - Crea enlace simbolico para archivos publicos

---

## USUARIO ADMIN CREADO

El seeder crea automaticamente:

| Campo | Valor |
|-------|-------|
| name | Super Admin |
| email | admin@cobro.test |
| password | 123456 |
| role | admin |
| balance | 0.00 |
| active | true |

**Login:** https://cobro-transporte-production-c773.up.railway.app/admin
- Email: `admin@cobro.test`
- Password: `123456`

---

## PASOS PARA APLICAR

### 1. En Railway -> Tu servicio web -> Variables

1. Click en "RAW Editor"
2. Borra todo lo que este ahi
3. Pega las variables de arriba
4. Click "Update Variables"

### 2. Esperar redeploy

Railway redesplegara automaticamente (3-5 minutos)

### 3. Verificar en logs

Deberias ver:
- "Running migrations..."
- "Seeding database..."
- "Linking storage..."

### 4. Probar

Ve a: https://cobro-transporte-production-c773.up.railway.app/admin
Login con: admin@cobro.test / 123456

---

## SI AUN HAY ERROR 500

### Opcion 1: Limpiar cache en Railway Shell

1. Railway -> Tu servicio -> Shell
2. Ejecuta:
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

### Opcion 2: Verificar logs

1. Railway -> Logs
2. Busca el error especifico
3. Comun: "No application encryption key"
   - Solucion: Verificar que APP_KEY tenga valor

### Opcion 3: Redeploy limpio

1. Railway -> Deployments
2. Click en los 3 puntos del ultimo
3. "Redeploy" con "Clear build cache"

---

## CONEXION A BASE DE DATOS

Para conectar desde tu PC local:

```bash
mysql -h yamabiko.proxy.rlwy.net -u root -p --port 31069 --protocol=TCP railway
```
Password: `bRNdtzEvgeCKDITnwxMBWIrNzdtAfCge`

---

## NOTAS IMPORTANTES

1. **No uses `chmod 777`** - Es inseguro, usa 775
2. **Siempre usa `--force`** - Para migraciones y seeders en produccion
3. **APP_KEY es critica** - Sin ella, el login falla
4. **SESSION_SECURE_COOKIE=true** - Necesario para HTTPS
5. **SESSION_DOMAIN=.railway.app** - Necesario para evitar 419

# GUIA COMPLETA DE DEPLOYMENT EN RAILWAY

## Servicios 100% Gratuitos

**Estado actual:** Proyecto en GitHub, Base de datos MySQL creada en Railway
**Objetivo:** Desplegar el backend Laravel completo en Railway

---

## PREREQUISITOS COMPLETADOS

- [x] Codigo en GitHub
- [x] MySQL creado en Railway
- [x] Base de datos migrada
- [ ] Servicio web desplegado

---

## PASO 1: CREAR EL SERVICIO WEB EN RAILWAY

### 1.1 Acceder a Railway

1. Ve a https://railway.app/
2. Inicia sesion con GitHub
3. Abre tu proyecto existente (donde esta MySQL)

### 1.2 Agregar Servicio Web

1. Click en **"+ New"** dentro de tu proyecto
2. Selecciona **"GitHub Repo"**
3. Busca y selecciona tu repositorio: `cobro-transporte`
4. Railway detectara automaticamente el Dockerfile

### 1.3 Esperar Build Inicial

- Railway comenzara a construir
- Tomara 5-10 minutos la primera vez
- Puedes ver los logs en tiempo real

---

## PASO 2: CONFIGURAR VARIABLES DE ENTORNO

### 2.1 Acceder a Variables

1. Click en el servicio web (el que acabas de crear)
2. Ve a la pestana **"Variables"**
3. Click en **"RAW Editor"** (esquina superior derecha)

### 2.2 Pegar Configuracion Completa

**IMPORTANTE:** Reemplaza los valores de base de datos con los tuyos de Railway MySQL.

```env
# ======================
# APLICACION
# ======================
APP_NAME=Interflow
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:qPmXQYhyMZL3R+lP79RIXkUbNN5C+qQvta17e9MbpQY=
APP_TIMEZONE=America/La_Paz
APP_URL=https://TU-PROYECTO.up.railway.app

# ======================
# BASE DE DATOS
# ======================
# Obtener estos valores de tu MySQL en Railway -> Variables
DB_CONNECTION=mysql
DB_HOST=mainline.proxy.rlwy.net
DB_PORT=44459
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=TU_PASSWORD_DE_RAILWAY

# ======================
# SESION (Importante para evitar error 419)
# ======================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.railway.app

# ======================
# CACHE Y SISTEMA
# ======================
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log

# ======================
# LOGS
# ======================
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ======================
# SANCTUM (Autenticacion API)
# ======================
SANCTUM_STATEFUL_DOMAINS=TU-PROYECTO.up.railway.app,localhost,127.0.0.1

# ======================
# MAIL (Opcional)
# ======================
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@interflow.com
MAIL_FROM_NAME=Interflow
```

### 2.3 Obtener Valores de MySQL

Para obtener los valores correctos de DB_*:

1. En tu proyecto Railway, click en el servicio **MySQL**
2. Ve a la pestana **"Variables"**
3. Copia estos valores:
   - `MYSQL_HOST` -> `DB_HOST`
   - `MYSQL_PORT` -> `DB_PORT`
   - `MYSQL_DATABASE` -> `DB_DATABASE`
   - `MYSQL_USER` -> `DB_USERNAME`
   - `MYSQL_PASSWORD` -> `DB_PASSWORD`

### 2.4 Actualizar URL del Proyecto

1. Una vez que Railway asigne URL a tu proyecto
2. Actualiza estas variables:
   - `APP_URL=https://tu-url-real.up.railway.app`
   - `SANCTUM_STATEFUL_DOMAINS=tu-url-real.up.railway.app,localhost`

### 2.5 Guardar Variables

1. Click en **"Update Variables"**
2. Railway redesplegara automaticamente
3. Espera 2-3 minutos

---

## PASO 3: VERIFICAR DEPLOYMENT

### 3.1 Comprobar Estado

1. En Railway, ve a la pestana **"Deployments"**
2. El ultimo deployment debe estar en **verde** (Success)
3. Si esta en rojo, revisa los logs

### 3.2 Probar URL

1. Copia la URL de tu proyecto
   - La encuentras en **Settings -> Domains**
   - O en la parte superior del servicio
2. Abre en el navegador: `https://tu-proyecto.up.railway.app`
3. Deberias ver la pantalla de bienvenida

### 3.3 Probar Panel Admin

1. Ve a: `https://tu-proyecto.up.railway.app/admin`
2. Intenta iniciar sesion con un usuario admin
3. Si funciona, el deployment esta completo

---

## PASO 4: LIMPIAR CACHES DE LARAVEL

### 4.1 Acceder a Shell

1. En Railway, click en tu servicio web
2. Ve a la pestana **"Shell"**
3. Se abrira una terminal

### 4.2 Ejecutar Comandos

Ejecuta estos comandos UNO POR UNO:

```bash
php artisan config:clear
```

```bash
php artisan cache:clear
```

```bash
php artisan route:clear
```

```bash
php artisan view:clear
```

```bash
php artisan config:cache
```

```bash
php artisan route:cache
```

### 4.3 Verificar Migraciones

```bash
php artisan migrate:status
```

Deberias ver todas las migraciones en estado "Ran".

---

## PASO 5: SOLUCIONAR ERROR 419 (SI APARECE)

El error 419 es un problema comun de CSRF en produccion.

### 5.1 Verificar Variables

En Railway Shell, ejecuta:

```bash
php artisan tinker
```

Luego escribe:

```php
echo config('app.url');
echo config('session.secure');
echo config('session.domain');
exit
```

Deberias ver:
- Tu URL de Railway
- `true` (o `1`)
- `.railway.app`

### 5.2 Si las Variables no estan Correctas

1. Vuelve a Variables en Railway
2. Verifica que SESSION_SECURE_COOKIE=true
3. Verifica que SESSION_DOMAIN=.railway.app
4. Guarda y espera el redeploy
5. Ejecuta `php artisan config:cache` en Shell

### 5.3 Borrar Cookies del Navegador

1. F12 -> Application -> Cookies
2. Borra todas las cookies del dominio
3. Recarga la pagina

---

## PASO 6: CONFIGURAR DOMINIO PERSONALIZADO (OPCIONAL)

### 6.1 Agregar Dominio

1. En Railway, ve a **Settings -> Domains**
2. Click **"Add Custom Domain"**
3. Escribe tu dominio: `interflow.tudominio.com`

### 6.2 Configurar DNS

Railway te dara un registro CNAME. Configura en tu proveedor DNS:

```
Tipo: CNAME
Nombre: interflow
Valor: [lo que Railway te indique].railway.app
```

### 6.3 Actualizar Variables

Actualiza APP_URL y SANCTUM_STATEFUL_DOMAINS con tu nuevo dominio.

---

## PASO 7: MONITOREO Y LOGS

### 7.1 Ver Logs en Tiempo Real

1. Railway -> Tu servicio -> Logs
2. Usa los filtros para buscar errores
3. Los logs muestran:
   - Peticiones HTTP
   - Errores PHP
   - Mensajes de Laravel

### 7.2 Verificar Uso de Recursos

Railway muestra:
- **CPU** - Uso del procesador
- **RAM** - Memoria (limite: 512MB en plan gratis)
- **Disk** - Almacenamiento (limite: 1GB)

### 7.3 Creditos Gratuitos

- Railway da **$5 USD gratis** al mes
- Monitorea en Settings -> Billing
- El servicio se pausa si se acaban

---

## PASO 8: ACTUALIZAR EL PROYECTO

### 8.1 Hacer Cambios Locales

```bash
# Edita tus archivos
# Luego:
git add .
git commit -m "descripcion del cambio"
git push origin master
```

### 8.2 Deploy Automatico

- Railway detecta el push automaticamente
- Reconstruye el proyecto
- Despliega la nueva version
- Zero downtime si todo va bien

### 8.3 Rollback si Hay Problemas

1. Railway -> Deployments
2. Busca un deployment anterior que funcionaba
3. Click en los 3 puntos -> **"Rollback"**

---

## PASO 9: PROBAR TODOS LOS ENDPOINTS

### 9.1 Panel Admin
- [x] Login admin funciona
- [x] Dashboard carga estadisticas
- [x] CRUD usuarios
- [x] CRUD tarjetas
- [x] Recargar saldo

### 9.2 API Publica
```bash
# Probar login
curl -X POST https://tu-proyecto.up.railway.app/api/cliente/login \
  -H "Content-Type: application/json" \
  -d '{"email":"conductor@test.com","password":"123456"}'
```

### 9.3 App Movil (PWA)
1. Abrir URL en Chrome del celular
2. Instalar como app (Agregar a inicio)
3. Probar login pasajero y conductor

---

## PASO 10: CONFIGURAR ARDUINO PARA PRODUCCION

Una vez que tengas la URL de Railway, actualiza tu codigo Arduino:

```cpp
// CAMBIAR ESTAS LINEAS:
const char* server_url = "https://TU-PROYECTO.up.railway.app";

// WiFi del chofer
const char* ssid = "NOMBRE_RED_CHOFER";
const char* password = "PASSWORD_RED";
```

Ver archivo `ARDUINO_PRODUCCION.md` para el codigo completo.

---

## TROUBLESHOOTING

### Error: Build Failed

**Causa:** Dependencias o Dockerfile con problemas

**Solucion:**
1. Ver logs del build
2. Buscar linea especifica del error
3. Comun: falta memoria -> usar Dockerfile optimizado

### Error: 500 Internal Server Error

**Causa:** Configuracion de Laravel incorrecta

**Solucion:**
1. Verificar APP_KEY
2. Ejecutar `php artisan config:cache`
3. Ver logs para error especifico

### Error: Database Connection Refused

**Causa:** Variables DB_* incorrectas

**Solucion:**
1. Verificar que MySQL este Active
2. Copiar valores exactos de MySQL -> Variables
3. El host NO es localhost

### Error: CORS

**Causa:** App movil no puede conectar

**Solucion:**
1. Verificar SANCTUM_STATEFUL_DOMAINS
2. Agregar dominio de Railway

---

## RESUMEN DE URLs

Una vez desplegado, tendras:

| Recurso | URL |
|---------|-----|
| Pagina Principal | `https://tu-proyecto.up.railway.app` |
| Panel Admin | `https://tu-proyecto.up.railway.app/admin` |
| Login Conductor | `https://tu-proyecto.up.railway.app/login-driver` |
| Login Pasajero | `https://tu-proyecto.up.railway.app/login-passenger` |
| API Base | `https://tu-proyecto.up.railway.app/api/` |

---

## COSTOS

| Servicio | Plan Gratis | Limite |
|----------|-------------|--------|
| Railway Web | $5/mes creditos | 512MB RAM, 1GB Disk |
| Railway MySQL | Incluido | 5GB storage |
| **Total** | **$0/mes** | Suficiente para proyecto academico |

---

**El proyecto esta listo para produccion en Railway!**

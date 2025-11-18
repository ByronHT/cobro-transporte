# ✅ CHECKLIST DE DESPLIEGUE EN RAILWAY

## Pre-Deployment (Completado ✅)

- [x] Proyecto limpio de archivos innecesarios
- [x] `.gitignore` actualizado para evitar subir archivos sensibles
- [x] `Dockerfile` optimizado para Railway
- [x] `docker-entrypoint.sh` creado con configuraciones automáticas
- [x] `.env.example` actualizado con variables de Railway
- [x] Migraciones verificadas (31 migraciones en total)
- [x] Documentación completa creada
- [x] Cambios listos para commit

## Pre-Requisitos Railway

### 1. Cuentas Necesarias
- [ ] Cuenta en Railway.app creada
- [ ] Railway conectado con tu cuenta de GitHub

### 2. Base de Datos MySQL en Railway
- [ ] MySQL creado en Railway
- [ ] Variables de conexión guardadas:
  ```
  DB_HOST=mainline.proxy.rlwy.net
  DB_PORT=44459
  DB_DATABASE=railway
  DB_USERNAME=root
  DB_PASSWORD=EikcJRVuHWfiEXdewQpuffjuVfsLcoKN
  ```
- [ ] Base de datos migrada (ya lo hiciste según mencionas)

## Paso 1: Subir Cambios a GitHub

```bash
# 1. Agregar todos los cambios
git add .

# 2. Verificar qué se va a subir
git status

# 3. Hacer commit
git commit -m "feat: Proyecto limpio y optimizado para Railway deployment

- Eliminados archivos de documentación innecesarios
- Actualizado Dockerfile para Railway
- Creado docker-entrypoint.sh con auto-configuración
- Actualizado .env.example con variables de Railway
- Actualizado .gitignore
- Agregadas guías de deployment (RAILWAY_DEPLOY.md, README.md)"

# 4. Subir a GitHub
git push origin master
```

## Paso 2: Crear Proyecto en Railway

1. [ ] Ir a https://railway.app/
2. [ ] Click en "New Project"
3. [ ] Seleccionar "Deploy from GitHub repo"
4. [ ] Seleccionar: `ByronHT/cobro-transporte`
5. [ ] Railway detectará automáticamente el Dockerfile

## Paso 3: Configurar Variables de Entorno en Railway

**Ir a:** Tu Proyecto → Variables → Raw Editor

Pega EXACTAMENTE estas variables (actualiza los valores según tus datos):

```bash
APP_NAME=Interflow
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:qPmXQYhyMZL3R+lP79RIXkUbNN5C+qQvta17e9MbpQY=
APP_TIMEZONE=America/La_Paz
APP_URL=https://cobro-transporte-production.up.railway.app

# Base de Datos (TUS VALORES DE RAILWAY MYSQL)
DB_CONNECTION=mysql
DB_HOST=mainline.proxy.rlwy.net
DB_PORT=44459
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=EikcJRVuHWfiEXdewQpuffjuVfsLcoKN

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

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

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,cobro-transporte-production.up.railway.app
```

**IMPORTANTE:**
- [ ] Reemplaza `APP_URL` con tu URL de Railway
- [ ] Reemplaza las variables `DB_*` con tus datos reales de Railway MySQL
- [ ] Verifica que `APP_KEY` tenga el prefijo `base64:`

## Paso 4: Deploy Automático

Railway comenzará automáticamente a:
1. [ ] Clonar el repositorio
2. [ ] Construir la imagen Docker (5-10 minutos)
3. [ ] Ejecutar el contenedor
4. [ ] Asignar un dominio público

**Monitor del Build:**
- [ ] Ver logs en tiempo real en Railway
- [ ] Esperar mensaje: "Build successful"
- [ ] Esperar estado: "Active" (verde)

## Paso 5: Verificación Post-Deploy

### 5.1 Verificar Aplicación Web
- [ ] Abrir URL de Railway: `https://tu-proyecto.railway.app`
- [ ] Debe cargar la página principal sin errores

### 5.2 Verificar Panel Admin
- [ ] Ir a: `https://tu-proyecto.railway.app/admin`
- [ ] Intentar login con usuario admin
- [ ] Verificar que cargue el dashboard

### 5.3 Verificar API
- [ ] Probar endpoint de health check (si existe)
- [ ] Probar login de API: `POST /api/cliente/login`

### 5.4 Verificar Base de Datos
```bash
# Conectar desde Railway Shell
# Railway → Tu Servicio → Shell

php artisan migrate:status
php artisan db:show
```

- [ ] Todas las migraciones ejecutadas
- [ ] Tablas existentes en la BD

## Paso 6: Configuración Final

### 6.1 Dominio Personalizado (Opcional)
- [ ] Railway → Settings → Domains
- [ ] Agregar tu dominio personalizado
- [ ] Configurar DNS según instrucciones

### 6.2 Verificar Logs
- [ ] Railway → Logs
- [ ] No debe haber errores críticos
- [ ] Solo warnings normales de Laravel

### 6.3 Performance
- [ ] Tiempo de carga < 3 segundos
- [ ] APIs responden correctamente
- [ ] No hay errores 500

## Paso 7: Actualizar Arduino ESP8266

Una vez que tengas la URL de Railway, actualiza el código Arduino:

```cpp
// En tu código Arduino, cambiar:
const char* server_url = "https://TU-URL-DE-RAILWAY.railway.app";

// Ejemplo:
const char* server_url = "https://cobro-transporte-production.up.railway.app";
```

- [ ] Código Arduino actualizado con URL de Railway
- [ ] WiFi configurado con red del chofer
- [ ] Subir código a ESP8266
- [ ] Verificar en Monitor Serial que se conecta

## Paso 8: Pruebas End-to-End

### 8.1 Flujo Completo de Pago
1. [ ] Admin crea un viaje desde panel admin
2. [ ] Arduino recibe comando de inicio
3. [ ] Pasajero acerca tarjeta RFID
4. [ ] Arduino procesa pago vía API
5. [ ] Transacción se registra en BD
6. [ ] Saldo actualizado en panel de pasajero

### 8.2 Panel de Conductor
- [ ] Conductor puede ver viajes activos
- [ ] Conductor puede finalizar viaje

### 8.3 Panel de Pasajero
- [ ] Pasajero ve su saldo
- [ ] Pasajero ve historial de viajes
- [ ] Pasajero puede solicitar devoluciones

## Troubleshooting

### ❌ Build Failed en Railway
**Solución:**
1. Verificar logs de build en Railway
2. Buscar línea específica del error
3. Verificar que `composer.json` y `package.json` estén en el repo

### ❌ Error 500 al cargar la app
**Solución:**
1. Railway → Logs → Buscar error específico
2. Verificar que `APP_KEY` esté configurado
3. Ejecutar en Shell:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### ❌ No conecta a base de datos
**Solución:**
1. Verificar variables `DB_*` en Railway
2. Verificar que MySQL esté "Active" en Railway
3. Probar conexión desde Shell:
   ```bash
   php artisan db:show
   ```

### ❌ Arduino no se conecta
**Solución:**
1. Verificar URL en código Arduino
2. Verificar que WiFi del chofer esté activo
3. Verificar en Monitor Serial:
   - "WiFi Connected"
   - "Esperando comandos..."

## Monitoreo Continuo

### Uso de Recursos Railway
- [ ] Verificar uso de RAM (límite: 512MB)
- [ ] Verificar uso de disco (límite: 1GB)
- [ ] Verificar créditos mensuales ($5 gratis)

### Logs
- [ ] Revisar logs diariamente
- [ ] Configurar alertas en Railway (si está disponible)

## Actualizaciones Futuras

Para actualizar el proyecto:
1. Hacer cambios en tu código local
2. Commit y push a GitHub
3. Railway detectará automáticamente y redesplegará

```bash
git add .
git commit -m "Descripción de cambios"
git push origin master
```

Railway automáticamente:
- ✅ Detectará el push
- ✅ Reconstruirá la imagen
- ✅ Desplegará la nueva versión
- ✅ Zero downtime (si está configurado)

---

## 📊 Estado del Proyecto

**Pre-Deploy:** ✅ COMPLETADO
**GitHub:** ⏳ PENDIENTE (hacer push)
**Railway Setup:** ⏳ PENDIENTE
**Verificación:** ⏳ PENDIENTE

---

## 📞 Soporte

Si encuentras problemas:
1. Revisar logs en Railway
2. Consultar `RAILWAY_DEPLOY.md`
3. Consultar `MIGRACION_A_PRODUCCION.md`

¡Buena suerte con el deployment! 🚀

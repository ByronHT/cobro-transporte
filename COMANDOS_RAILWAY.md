# 🚀 Comandos para Railway - Interflow

## 📋 Índice de Problemas y Soluciones

1. [Ejecutar migraciones después del deploy](#1-ejecutar-migraciones)
2. [Solucionar index.html roto (Laravel no funciona)](#2-solucionar-indexhtml-roto)
3. [Limpiar datos GPS antiguos](#3-limpiar-datos-gps-antiguos)
4. [Verificar tracking GPS](#4-verificar-tracking-gps)
5. [Ver logs en tiempo real](#5-ver-logs)

---

## 1. Ejecutar Migraciones

**Cuándo:** Después de hacer deploy en Railway

```bash
# Conectar a Railway
railway run bash

# Ejecutar migraciones
php artisan migrate --force

# Verificar que se crearon las tablas/índices
php artisan migrate:status
```

**Qué hace:**
- Crea índices en `bus_locations` para optimizar búsquedas GPS
- Agrega campos necesarios si faltan

---

## 2. Solucionar index.html Roto

**Problema:** Después de `npm run build`, el panel web de Laravel no funciona porque `public/index.html` fue sobrescrito por el de React/Capacitor.

**Síntomas:**
- Al entrar a Railway URL sale la app móvil de React
- No puedes acceder a `/login`, `/admin`, etc.

### ✅ Solución Rápida:

```bash
# Conectar a Railway
railway run bash

# Eliminar index.html de React/Capacitor
rm public/index.html

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# ✅ Listo - Laravel ahora manejará las rutas correctamente
```

### 🔄 Solución Alternativa (Usar el script):

```bash
# Conectar a Railway
railway run bash

# Dar permisos al script
chmod +x fix-index-laravel.sh

# Ejecutar el script
./fix-index-laravel.sh
```

**Qué hace:**
- Elimina `public/index.html` que genera Vite para React
- Laravel vuelve a manejar las rutas con `routes/web.php`
- El panel de admin funciona de nuevo

---

## 3. Limpiar Datos GPS Antiguos

**Cuándo:** Cuando la tabla `bus_locations` tenga demasiados registros (causa lentitud)

```bash
# Conectar a Railway
railway run bash

# Limpiar registros de más de 7 días
php artisan bus-locations:cleanup --days=7

# O limpiar registros de más de 30 días
php artisan bus-locations:cleanup --days=30
```

**Qué hace:**
- Elimina registros GPS antiguos en lotes de 1000
- Optimiza la tabla después de eliminar
- Mejora el rendimiento de las búsquedas

**Recomendación:** Ejecutar cada semana o configurar un cron job.

---

## 4. Verificar Tracking GPS

**Problema:** Chofer inició viaje pero su ubicación no aparece en el mapa

### Verificar registros GPS:

```bash
# Conectar a Railway
railway run bash

# Ver últimas ubicaciones GPS registradas
php artisan tinker
>>> BusLocation::latest()->take(10)->get(['bus_id', 'latitude', 'longitude', 'is_active', 'recorded_at'])

# Ver buses con viajes activos
>>> BusLocation::where('is_active', true)->latest()->take(5)->get()
```

### Verificar viajes activos:

```bash
php artisan tinker
>>> Trip::whereNull('fin')->with('bus', 'driver')->get()
```

---

## 5. Ver Logs en Tiempo Real

**Para debugging en Railway:**

```bash
# Ver logs de Laravel
railway logs

# O conectar y ver archivo de logs
railway run bash
tail -f storage/logs/laravel.log

# Ver solo errores GPS
tail -f storage/logs/laravel.log | grep GPS
```

---

## 🔄 Flujo Completo de Deploy

```bash
# 1. Hacer push a GitHub (ya lo hiciste)
git push origin master

# 2. Railway hace deploy automático
# Esperar a que termine el deploy...

# 3. Ejecutar migraciones
railway run php artisan migrate --force

# 4. Si el panel web no funciona, eliminar index.html
railway run rm public/index.html
railway run php artisan cache:clear

# 5. Verificar que funcione
# Abrir: https://tu-app.up.railway.app/login

# 6. (Opcional) Limpiar datos GPS viejos
railway run php artisan bus-locations:cleanup --days=7
```

---

## 📱 Para Android (Cuando generes APK)

**Problema Similar:** Después de `npm run build`, Android genera su `index.html` y rompe Laravel.

### Solución antes de hacer push a Railway:

```bash
# En local, después de generar APK
npm run build

# Eliminar index.html antes de hacer commit
rm public/index.html

# O agregar a .gitignore
echo "public/index.html" >> .gitignore

# Hacer commit y push
git add .
git commit -m "build: Generar APK Android"
git push origin master
```

### O arreglarlo después en Railway:

```bash
railway run rm public/index.html
railway run php artisan cache:clear
```

---

## ⚠️ Nota Importante

**`public/index.html` NO DEBE EXISTIR para que Laravel funcione correctamente.**

- ✅ Laravel maneja rutas con `routes/web.php`
- ✅ React se sirve desde assets compilados (`public/build/assets/`)
- ❌ `public/index.html` sobrescribe todo y rompe Laravel

**Solución permanente:** Modificar `postbuild.js` para que NO genere `public/index.html` en producción.

---

## 🆘 Si nada funciona

```bash
# Conectar a Railway
railway run bash

# Reset completo
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Eliminar index.html
rm public/index.html

# Regenerar optimizaciones
php artisan optimize

# Reiniciar servicio en Railway Dashboard
```

---

## 📞 Debugging Avanzado

### Ver qué archivo está sirviendo:

```bash
railway run bash
ls -la public/ | grep index
```

### Ver configuración de rutas:

```bash
php artisan route:list | grep "/"
```

### Verificar assets compilados:

```bash
ls -la public/build/assets/
cat public/build/manifest.json
```

---

**Última actualización:** 2025-11-20
**Versión Laravel:** 9
**Versión Vite:** 7.1.5

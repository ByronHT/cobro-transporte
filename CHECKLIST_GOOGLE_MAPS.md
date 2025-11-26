# ✅ CHECKLIST - VERIFICACIÓN GOOGLE MAPS

## 📋 CONFIGURACIÓN EN GOOGLE CLOUD CONSOLE

### API Key para WEB (JavaScript API)

**URL:** https://console.cloud.google.com/apis/credentials

**Restricción de aplicación:** HTTP referrers (sitios web)

**Sitios web autorizados:**
- [ ] `https://cobro-transporte-production-dac4.up.railway.app/*`
- [ ] `http://localhost:8000/*`
- [ ] `http://127.0.0.1:8000/*`
- [ ] `http://10.217.3.233:8000/*` (IP local actual)

**Restricciones de API:**
- [ ] Maps JavaScript API
- [ ] Places API

---

### API Key para ANDROID (Maps SDK)

**Restricción de aplicación:** Aplicaciones de Android

**Aplicaciones autorizadas:**
```
Nombre del paquete: com.interflow.app
SHA-1: [TU_SHA1_AQUI]
```

**Restricciones de API:**
- [ ] Maps SDK for Android
- [ ] Places API

---

## 📋 CONFIGURACIÓN EN RAILWAY

**Variables de entorno configuradas:**

- [ ] `VITE_GOOGLE_MAPS_API_KEY=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g`
- [ ] `GOOGLE_MAPS_API_KEY_WEB=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g`
- [ ] `GOOGLE_MAPS_API_KEY_ANDROID=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g`

**Estado del deploy:**
- [ ] Deploy completado sin errores
- [ ] Logs no muestran errores de Google Maps

---

## 📋 PRUEBA EN LOCALHOST

**URL:** http://10.217.3.233:8000

**Pasos:**
1. [ ] Servidor Laravel corriendo (`php artisan serve`)
2. [ ] Login como pasajero
3. [ ] Click en "Buscar Línea"
4. [ ] Seleccionar una ruta
5. [ ] Ver mapa de Google Maps (no OpenStreetMap)
6. [ ] Ver marcadores de buses
7. [ ] Ver ubicación del usuario
8. [ ] Click en bus muestra información

**Resultado esperado:**
- [ ] ✅ Mapa de Google Maps carga correctamente
- [ ] ✅ No hay errores en consola del navegador (F12)
- [ ] ✅ Marcadores funcionan correctamente

---

## 📋 PRUEBA EN RAILWAY (PRODUCCIÓN)

**URL:** https://cobro-transporte-production-dac4.up.railway.app

**Pasos:**
1. [ ] Login como pasajero
2. [ ] Click en "Buscar Línea"
3. [ ] Seleccionar una ruta
4. [ ] Ver mapa de Google Maps

**Resultado esperado:**
- [ ] ✅ Mapa de Google Maps carga correctamente
- [ ] ✅ Marcadores de buses aparecen
- [ ] ✅ Ubicación del usuario funciona

---

## 📋 PRUEBA EN ANDROID

**Pasos de compilación:**

1. [ ] Ejecutar: `npm run build`
2. [ ] Ejecutar: `npx cap sync android`
3. [ ] Ejecutar: `npx cap open android`
4. [ ] Build APK en Android Studio
5. [ ] Instalar APK en dispositivo

**Pruebas en dispositivo:**
1. [ ] Abrir app Interflow
2. [ ] Login como pasajero
3. [ ] Click en "Buscar Línea"
4. [ ] Seleccionar una ruta
5. [ ] Ver mapa de Google Maps

**Resultado esperado:**
- [ ] ✅ Mapa carga correctamente
- [ ] ✅ Marcadores de buses visibles
- [ ] ✅ GPS del usuario funciona
- [ ] ✅ No hay errores de autenticación

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "API Key no configurada"
**Causa:** Variable `VITE_GOOGLE_MAPS_API_KEY` no está en Railway
**Solución:** Agregar variable en Railway > Variables

### Error: "This page can't load Google Maps correctly"
**Causa:** Restricciones de API Key incorrectas
**Solución:** Verificar restricciones HTTP en Google Cloud Console

### Error: "RefererNotAllowedMapError"
**Causa:** El dominio no está en las restricciones
**Solución:** Agregar dominio a las restricciones HTTP

### Mapa gris en Android
**Causa 1:** SHA-1 incorrecto en restricciones
**Solución:** Obtener SHA-1 con `gradlew.bat signingReport`

**Causa 2:** AndroidManifest.xml no tiene la API Key
**Solución:** Verificar que exista `<meta-data android:name="com.google.android.geo.API_KEY" ...>`

### Sigue mostrando OpenStreetMap
**Causa:** Railway no se actualizó
**Solución:** Forzar redeploy en Railway

---

## ✅ CONFIRMACIÓN FINAL

Una vez que TODO funcione:

**En LOCAL:**
- [ ] ✅ Google Maps carga
- [ ] ✅ Marcadores funcionan
- [ ] ✅ Sin errores en consola

**En RAILWAY:**
- [ ] ✅ Google Maps carga
- [ ] ✅ Variables configuradas
- [ ] ✅ Deploy exitoso

**En ANDROID:**
- [ ] ✅ Mapa nativo funciona
- [ ] ✅ GPS preciso
- [ ] ✅ Sin errores de autenticación

---

## 📞 SIGUIENTE PASO

Si TODO funciona en local pero NO en Railway:
- Verificar variables en Railway
- Forzar redeploy
- Revisar logs de build

Si TODO funciona en web pero NO en Android:
- Verificar SHA-1 en Google Cloud
- Verificar AndroidManifest.xml
- Recompilar APK

---

**Fecha de verificación:** _______________
**Estado:** ⬜ Pendiente | ⬜ En Proceso | ⬜ Completado

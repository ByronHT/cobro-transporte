# 🗺️ CONFIGURAR GOOGLE MAPS EN RAILWAY

## ⚠️ IMPORTANTE: Variables de Entorno

Railway **NO** lee el archivo `.env` automáticamente. Debes configurar las variables manualmente.

---

## 📝 PASO 1: Agregar Variables en Railway

### Opción A: Via Dashboard Web

1. Ve a tu proyecto en Railway: https://railway.app
2. Click en tu servicio "cobro-transporte"
3. Ve a la pestaña **"Variables"**
4. Click en **"RAW Editor"**
5. **Agrega estas 3 líneas AL FINAL:**

```env
GOOGLE_MAPS_API_KEY_WEB=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
GOOGLE_MAPS_API_KEY_ANDROID=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
VITE_GOOGLE_MAPS_API_KEY=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
```

6. Click en **"Update Variables"**
7. Railway reiniciará automáticamente el servicio

### Opción B: Via CLI Railway

```bash
# Instalar Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link al proyecto
railway link

# Agregar variables
railway variables set GOOGLE_MAPS_API_KEY_WEB=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
railway variables set GOOGLE_MAPS_API_KEY_ANDROID=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
railway variables set VITE_GOOGLE_MAPS_API_KEY=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
```

---

## 🔧 PASO 2: Verificar el Deploy

1. **Espera a que Railway termine de desplegar** (~5 minutos)
2. Ve a los **Logs** en Railway
3. Busca líneas como:
   ```
   ✓ built in 14.01s
   ✓ PassengerDashboard
   ```
4. Si ves errores, revisa que las variables estén bien escritas

---

## 🌐 PASO 3: Verificar en Web

1. Abre tu URL de Railway: `https://cobro-transporte-production-dac4.up.railway.app`
2. Inicia sesión como **pasajero**
3. Ve a **"Buscar Línea"**
4. Selecciona una ruta (ej: "Linea 79 - 4to anillo")
5. **Deberías ver el mapa de Google Maps** con los buses

### Si NO funciona:

#### Error: "API Key no configurada"
- Las variables NO se agregaron correctamente en Railway
- Vuelve a agregarlas y espera el redeploy

#### Error: "This page can't load Google Maps correctly"
- La API Key no es válida o tiene restricciones incorrectas
- Ve a Google Cloud Console y verifica:
  - ✅ Maps JavaScript API está habilitada
  - ✅ Restricciones HTTP incluyen tu dominio Railway

---

## 📱 PASO 4: Probar en App Android

### Compilar APK actualizado:

```bash
# 1. Compilar frontend
npm run build

# 2. Sincronizar con Capacitor
npx cap sync android

# 3. Abrir Android Studio
npx cap open android

# 4. Build > Build Bundle(s) / APK(s) > Build APK(s)
```

### Verificar en dispositivo:

1. Instala el APK en tu teléfono
2. Inicia sesión como pasajero
3. Ve a "Buscar Línea"
4. Selecciona una ruta
5. **Deberías ver Google Maps con los buses**

### Si NO funciona en Android:

#### Pantalla gris sin mapa:
- Verifica que el `AndroidManifest.xml` tenga la API Key:
  ```xml
  <meta-data
      android:name="com.google.android.geo.API_KEY"
      android:value="AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g" />
  ```
- Recompila el APK desde Android Studio

#### Error de autenticación:
- La API Key necesita restricción de Android con SHA-1
- Obtén el SHA-1:
  ```bash
  cd android
  gradlew.bat signingReport
  ```
- Agrega el SHA-1 en Google Cloud Console

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Web muestra OpenStreetMap en lugar de Google Maps

**Causa:** Railway no se actualizó con el nuevo código.

**Solución:**
```bash
# 1. Forzar nuevo deploy
git commit --allow-empty -m "Force redeploy"
git push origin master

# 2. O en Railway Dashboard:
# Settings > Deployments > Redeploy
```

---

### Error: "Google Maps API error: RefererNotAllowedMapError"

**Causa:** Tu dominio no está en las restricciones HTTP.

**Solución:**
1. Ve a Google Cloud Console
2. APIs & Services > Credentials
3. Click en tu API Key
4. Application restrictions > HTTP referrers
5. **Agrega:**
   ```
   https://cobro-transporte-production-dac4.up.railway.app/*
   http://localhost:8000/*
   http://127.0.0.1:8000/*
   ```
6. Save

---

### Variable no se aplica en Railway

**Causa:** Railway cachea las variables viejas.

**Solución:**
1. Settings > General
2. Click en **"Restart"**
3. O elimina la variable y agrégala de nuevo

---

## ✅ CHECKLIST FINAL

Antes de considerar que funciona:

**En Railway:**
- [ ] Las 3 variables de Google Maps están configuradas
- [ ] El deploy terminó exitosamente
- [ ] Los logs no muestran errores

**En Google Cloud Console:**
- [ ] Maps JavaScript API está habilitada
- [ ] API Key tiene restricciones HTTP con Railway
- [ ] API Key tiene restricciones Android (para app)

**En Web:**
- [ ] Puedes ver el mapa de Google Maps
- [ ] Los marcadores de buses aparecen
- [ ] Click en bus muestra información

**En Android:**
- [ ] APK recompilado con AndroidManifest actualizado
- [ ] Mapa de Google Maps carga correctamente
- [ ] Marcadores de buses funcionan

---

## 📞 SOPORTE

Si después de seguir todos los pasos aún no funciona:

1. **Revisa los logs de Railway:**
   ```bash
   railway logs
   ```

2. **Revisa la consola del navegador:**
   - F12 > Console
   - Busca errores de Google Maps

3. **Verifica la API Key en Google Cloud:**
   - Ve a "Metrics" y verifica que haya requests
   - Si no hay requests, la API Key no está siendo usada

---

## 🎯 RESUMEN RÁPIDO

```bash
# 1. Agregar variables en Railway (3 variables)
GOOGLE_MAPS_API_KEY_WEB=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
GOOGLE_MAPS_API_KEY_ANDROID=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g
VITE_GOOGLE_MAPS_API_KEY=AIzaSyB1ZmOxDHBgVFwgi0GxXA85HR-cXf6sx8g

# 2. Esperar redeploy de Railway (5 min)

# 3. Probar en web
https://cobro-transporte-production-dac4.up.railway.app

# 4. Si funciona en web, compilar APK para Android
npm run build
npx cap sync android
npx cap open android
```

---

¡Listo! Con esto Google Maps debería funcionar en web y Android. 🎉

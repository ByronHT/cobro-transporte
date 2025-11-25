# 📊 ESTADO ACTUAL DEL PROYECTO INTERFLOW

**Fecha:** 25 de Noviembre, 2025
**Versión:** 1.0
**Última Actualización:** Commit `26ff455`

---

## 🎯 RESUMEN EJECUTIVO

Sistema de cobro electrónico para transporte público con app móvil Android, backend Laravel y dispositivo Arduino/ESP8266 para lectura NFC.

**Estado General:** ✅ **FUNCIONAL EN DESARROLLO**

---

## 📱 TECNOLOGÍAS IMPLEMENTADAS

### Backend
- **Laravel:** 9.52.20
- **Base de Datos:** MySQL
- **Autenticación:** Laravel Sanctum
- **API REST:** Endpoints para móvil y Arduino
- **Deploy:** Railway (https://cobro-transporte-production-dac4.up.railway.app)

### Frontend/Móvil
- **React:** 19.2.0
- **Capacitor:** 7.4.4 (Android)
- **Vite:** 7.1.5
- **Mapas:** Leaflet + OpenStreetMap
- **Notificaciones:** @capacitor/local-notifications@7.0.3
- **Cámara:** @capacitor/camera@7.0.2

### Hardware
- **Dispositivo:** Arduino/ESP8266
- **Tecnología:** NFC/RFID
- **Display:** LCD 16x2
- **Comunicación:** HTTP API

---

## ✅ FUNCIONALIDADES IMPLEMENTADAS

### 🚗 Panel del Chofer
- ✅ Iniciar/Finalizar viajes
- ✅ GPS tracking en tiempo real (throttling inteligente)
- ✅ Historial de transacciones del viaje actual
- ✅ Eventos de pago (exitosos y fallidos)
- ✅ Sistema de reportes con foto
- ✅ Gestión de solicitudes de devolución
- ✅ Notificaciones en tiempo real sin duplicados

### 👤 Panel del Pasajero
- ✅ Visualizar saldo de tarjeta
- ✅ Código QR de la tarjeta
- ✅ Historial de viajes y transacciones
- ✅ Búsqueda de buses cercanos con mapa interactivo
- ✅ Filtrado por línea/ruta
- ✅ Sistema de quejas
- ✅ Notificaciones de pagos y eventos

### 💰 Sistema de Devoluciones
- ✅ Solicitud de devolución por pasajero
- ✅ Aprobación/Rechazo por chofer
- ✅ Reversión de devoluciones
- ✅ Verificación por token único
- ✅ Estados: pending, verified, completed, rejected

### 💳 Sistema de Pagos (Arduino)
- ✅ Lectura NFC de tarjetas
- ✅ Validación de tarjeta (activa/inactiva/no registrada)
- ✅ Validación de saldo
- ✅ Descuento automático de tarifa
- ✅ **Display LCD con nombre del pasajero**
- ✅ **Mensajes de error específicos (max 16 chars)**
- ✅ Registro de eventos de pago

### 📍 Sistema GPS
- ✅ Tracking automático con Capacitor Geolocation
- ✅ Throttling inteligente (30s quieto, 15s movimiento)
- ✅ Detección de movimiento significativo (>50m)
- ✅ Solo envía cuando hay viaje activo
- ✅ **Filtrado de ubicaciones duplicadas** (1 marcador por bus)

---

## 🔔 OPTIMIZACIONES RECIENTES

### Notificaciones Deduplicadas (Commit: 06a763f)
```javascript
// Set de tracking para evitar repeticiones
const notifiedEventsRef = useRef(new Set());

newEvents.forEach(event => {
    if (notifiedEventsRef.current.has(event.id)) return;
    notifiedEventsRef.current.add(event.id);
    showNotification(...);
});
```

### Polling en Tiempo Real (5 segundos)
```javascript
// config.js
export const POLLING_INTERVAL = 5000; // Antes: 60000

// Sin loading spinner en actualizaciones automáticas
const isFirstLoad = !driverData;
if (isFirstLoad) setLoading(true);
```

### Ubicaciones Duplicadas Solucionadas (Commit: fbbf08e)
```php
// BusLocation.php - Solo ubicación más reciente por bus
$latestLocationIds = self::selectRaw('MAX(id) as latest_id')
    ->where('is_active', true)
    ->groupBy('bus_id')
    ->pluck('latest_id');
```

---

## ⚠️ PROBLEMAS CONOCIDOS Y PENDIENTES

### 🔴 ALTA PRIORIDAD

#### 1. ❌ **Error en Vista de Devoluciones (Pasajero)**
**Síntoma:** Pantalla blanca con error "vt.filter is not a function"

**Ubicación:** `PassengerDashboard.jsx` - `renderDevolucionesScreen()`

**Causa:**
```javascript
// Línea ~1003
const fareTransactions = transactions.filter(tx => tx.type === 'fare').slice(0, 8);
```

**Problema:**
- `transactions` no es un array
- El endpoint `/api/transactions` devuelve `{ data: [...] }`
- Intentar ejecutar `.filter()` sobre un objeto causa el crash

**Solución Propuesta:**
```javascript
// Opción 1: Verificar estructura
const fareTransactions = (Array.isArray(transactions) ? transactions : transactions.data || [])
    .filter(tx => tx.type === 'fare')
    .slice(0, 8);

// Opción 2: Ajustar setTransactions en fetchData
const transactionsResponse = await apiClient.get('/api/transactions');
setTransactions(transactionsResponse.data.data || transactionsResponse.data);
```

**Impacto:** 🔴 **CRÍTICO** - La vista de devoluciones no funciona
**Estimación:** 30 minutos

---

### 🟡 MEDIA PRIORIDAD

#### 2. ⚠️ **Historial de Pagos con Devoluciones Incompleto**
- **Estado:** Parcialmente implementado
- **Falta:** Vista completa de historial en tab "Movimientos"
- **Impacto:** Usuarios no pueden ver todas las transacciones históricas
- **Estimación:** 2 horas

#### 3. ⚠️ **Radio de Búsqueda Limitado (5km)**
- **Actual:** BusTrackingController.php usa 5km por defecto
- **Propuesta:** Aumentar a 20km
- **Impacto:** Baja cobertura en zonas periféricas
- **Estimación:** 1 hora

---

### 🟢 BAJA PRIORIDAD

#### 4. ℹ️ **Optimización de Rendimiento**
- WebSockets/SSE para eventos en tiempo real
- Clustering de marcadores en mapa (muchos buses)
- Code splitting de la app React
- Índices adicionales en base de datos
- **Estimación:** 1-2 semanas

#### 5. ℹ️ **Chunk Size Warning**
```
⚠ Some chunks are larger than 500 kB after minification
app-bGse_m8-.js: 641.11 kB (gzip: 191.17 kB)
```
- **Solución:** Implementar dynamic imports
- **Impacto:** Tiempo de carga inicial lento
- **Estimación:** 1 día

---

## 📦 ESTRUCTURA DE ARCHIVOS CLAVE

### Backend
```
app/
├── Http/Controllers/API/
│   ├── PaymentController.php      ✅ LCD con nombre de pasajero
│   ├── BusTrackingController.php  ✅ Ubicaciones deduplicadas
│   ├── RefundController.php       ✅ Sistema completo
│   └── ComplaintController.php    ✅ Quejas
├── Models/
│   ├── BusLocation.php           ✅ findNearby() optimizado
│   ├── Transaction.php
│   ├── RefundRequest.php
│   └── User.php
```

### Frontend
```
resources/js/
├── components/
│   ├── DriverDashboard.jsx       ✅ Notificaciones deduplicadas
│   ├── PassengerDashboard.jsx    ❌ Error en devoluciones
│   ├── BusMap.jsx                ✅ Mapa con Leaflet
│   └── LoginUnificado.jsx
├── hooks/
│   ├── useGPSTracking.js         ✅ Throttling inteligente
│   └── useNativeNotifications.js ✅ Push notifications
└── config.js                     ✅ POLLING_INTERVAL = 5000
```

### Android
```
android/
├── app/src/main/AndroidManifest.xml  ✅ Permisos GPS + Notificaciones
├── app/build.gradle                  ✅ Configuración Capacitor
└── capacitor.config.json             ✅ appId: com.interflow.app
```

---

## 🔧 CONFIGURACIÓN ACTUAL

### Variables de Entorno (.env)
```env
APP_ENV=local
APP_KEY=base64:... ✅ Generada
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_DATABASE=cobro_tp
```

### API Base URL
```javascript
// config.js
export const API_BASE_URL = 'https://cobro-transporte-production-dac4.up.railway.app';
export const POLLING_INTERVAL = 5000; // 5 segundos
```

### GPS Tracking
```javascript
const INTERVAL_STATIONARY = 30000; // 30s quieto
const INTERVAL_MOVING = 15000;     // 15s movimiento
const MIN_DISTANCE_METERS = 50;    // Mínimo para considerar movimiento
```

---

## 📊 MÉTRICAS DEL PROYECTO

| Métrica | Valor |
|---------|-------|
| **Modelos Eloquent** | 12 |
| **Migraciones** | 34 |
| **Controladores API** | 11 |
| **Componentes React** | 9 |
| **Hooks Personalizados** | 2 |
| **Endpoints API** | ~30 |
| **Plugins Capacitor** | 3 |
| **Líneas de Código** | ~15,000 |

---

## 🚀 DEPLOY Y ENTORNOS

### Producción (Railway)
- **URL:** https://cobro-transporte-production-dac4.up.railway.app
- **Estado:** ✅ Operativo
- **Auto-deploy:** Habilitado desde master

### Desarrollo Local
- **Backend:** `php artisan serve` (localhost:8000)
- **Frontend:** `npm run dev` (Vite HMR)
- **Base de Datos:** XAMPP MySQL

### APK Android
- **Build:** `npx cap open android` → Build APK en Android Studio
- **Ubicación:** `android/app/build/outputs/apk/release/`
- **Version Code:** 1
- **Version Name:** 1.0

---

## 🔄 ÚLTIMOS COMMITS

```bash
26ff455 fix: Cargar transacciones en fetchData para devoluciones
fbbf08e fix: Solucionar ubicaciones duplicadas y cargar transactions
06a763f feat: Optimizar app móvil con notificaciones y polling
e8a948f feat: Mejorar sistema de pagos, limpiar proyecto
2bc5f9e docs: Agregar scripts y comandos Railway
```

---

## 📝 TAREAS INMEDIATAS

### Antes del Release 1.0:

- [ ] **URGENTE:** Solucionar error "vt.filter is not a function" en devoluciones
- [ ] Probar flujo completo de solicitud de devolución
- [ ] Validar notificaciones deduplicadas en dispositivo real
- [ ] Verificar mapa con múltiples buses activos
- [ ] Generar APK release firmado
- [ ] Documentar proceso de instalación para usuarios finales

### Post-Release:

- [ ] Implementar WebSockets para eventos en tiempo real
- [ ] Aumentar radio de búsqueda a 20km
- [ ] Optimizar bundle size (code splitting)
- [ ] Agregar tests unitarios críticos
- [ ] Implementar sistema de logs robusto

---

## 🎯 ESTADO DE FUNCIONALIDADES

| Funcionalidad | Estado | Prioridad | Notas |
|---------------|--------|-----------|-------|
| Sistema de Pagos Arduino | ✅ Completo | Alta | Con LCD y nombre de pasajero |
| GPS Tracking | ✅ Completo | Alta | Throttling optimizado |
| Notificaciones | ✅ Completo | Alta | Deduplicadas correctamente |
| Ubicaciones Duplicadas | ✅ Solucionado | Alta | 1 marcador por bus |
| **Vista Devoluciones** | ❌ **Rota** | **Alta** | **Error vt.filter** |
| Historial Completo | ⚠️ Parcial | Media | Falta integración completa |
| Radio Búsqueda | ⚠️ 5km | Media | Aumentar a 20km |
| Sistema de Quejas | ✅ Completo | Media | Funcional |
| Reportes con Foto | ✅ Completo | Media | Camera plugin OK |

---

## 🔐 PERMISOS ANDROID

```xml
<!-- AndroidManifest.xml -->
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_BACKGROUND_LOCATION" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" /> <!-- Android 13+ -->
```

---

## 📞 CONTACTO Y RECURSOS

- **Repositorio:** https://github.com/ByronHT/cobro-transporte
- **Deploy:** Railway (auto-deploy desde master)
- **Documentación API:** Swagger pendiente
- **Wiki:** Pendiente

---

## 🏁 CONCLUSIÓN

El proyecto **Interflow** está en un **estado funcional avanzado** con la mayoría de características implementadas y optimizadas. El único bloqueante crítico es el error en la vista de devoluciones del pasajero que debe solucionarse antes del release 1.0.

**Prioridad Inmediata:**
1. Fix error "vt.filter is not a function"
2. Pruebas exhaustivas en dispositivo Android real
3. Generación de APK release firmado

---

**Última Actualización:** 25 de Noviembre, 2025
**Estado:** ✅ Listo para corrección final

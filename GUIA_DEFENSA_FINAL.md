# GUIA PREPARATORIA PARA DEFENSA FINAL

## Sistema INTERFLOW - Sistema de Cobro de Transporte Publico

**Desarrollador:** Brandon
**Fecha de Defensa:** Noviembre 2025

---

## SECCION 1: RESUMEN EJECUTIVO DEL PROYECTO

### Que es INTERFLOW?
INTERFLOW es un sistema integral de cobro electronico para transporte publico que permite:

1. **Pago automatico** mediante tarjetas RFID
2. **Gestion administrativa** de usuarios, buses, rutas y tarjetas
3. **Panel de conductores** para control de viajes
4. **Panel de pasajeros** para consulta de saldo e historial
5. **Monitoreo en tiempo real** de viajes y transacciones

### Problema que Resuelve
- Eliminacion de manejo de efectivo en buses
- Registro automatico de transacciones
- Control de ingresos por ruta y conductor
- Transparencia en cobros y devoluciones
- Reduccion de evasion de pago

---

## SECCION 2: ARQUITECTURA TECNICA

### Stack Tecnologico

| Capa | Tecnologia | Version | Justificacion |
|------|------------|---------|---------------|
| **Backend** | Laravel | 9.x | Framework PHP robusto, ORM Eloquent, Sanctum para API |
| **Frontend Admin** | Blade + Tailwind | - | Renderizado servidor para panel admin |
| **Frontend Movil** | React + Ionic | 19.x | SPA moderna, componentes moviles |
| **Build Tool** | Vite | 7.x | Compilacion rapida, HMR |
| **Base de Datos** | MySQL | 8.x | Relacional, transacciones ACID |
| **Autenticacion** | Laravel Sanctum | 3.x | Tokens API seguros |
| **IoT** | ESP8266 + MFRC522 | - | WiFi integrado, bajo costo |

### Por que Laravel?
- **ORM Eloquent**: Simplifica consultas complejas
- **Migraciones**: Control de version de base de datos
- **Sanctum**: Autenticacion API sin complejidad de OAuth
- **Artisan**: Comandos CLI para desarrollo
- **Comunidad**: Gran documentacion y soporte

### Por que React con Ionic?
- **Componentes moviles**: UI optimizada para tactil
- **Single Page Application**: Experiencia fluida
- **PWA Ready**: Instalable como app nativa
- **Reutilizacion**: Mismo codigo web y movil

### Por que ESP8266?
- **WiFi integrado**: Conexion directa a internet
- **Bajo costo**: ~$3 USD por modulo
- **Compatible con Arduino**: Facil programacion
- **GPIO suficientes**: SPI para RFID

---

## SECCION 3: ESTRUCTURA DE BASE DE DATOS

### Diagrama Entidad-Relacion (Simplificado)

```
+----------+       +----------+       +----------+
|  users   |       |  cards   |       | rutas    |
+----------+       +----------+       +----------+
| id       |<---+  | id       |       | id       |
| name     |    |  | uid      |   +-->| nombre   |
| email    |    +--| passenger|   |   | tarifa   |
| role     |       | balance  |   |   +----------+
| balance  |       +----------+   |
+----------+           |          |
     |                 |          |
     v                 v          |
+----------+     +-------------+  |
|  trips   |     | transactions|  |
+----------+     +-------------+  |
| id       |<----| trip_id     |  |
| driver_id|     | card_id     |--+
| bus_id   |     | amount      |
| ruta_id  |---->| type        |
| inicio   |     +-------------+
| fin      |
+----------+
     |
     v
+----------+
|  buses   |
+----------+
| id       |
| plate    |
| ruta_id  |
+----------+
```

### Tablas Principales (12 tablas)

1. **users** - Administradores, conductores, pasajeros
2. **cards** - Tarjetas RFID con saldo
3. **rutas** - Rutas de transporte
4. **buses** - Vehiculos de la flota
5. **trips** - Viajes realizados
6. **transactions** - Movimientos financieros
7. **payment_events** - Auditoria de pagos
8. **refund_requests** - Solicitudes de devolucion
9. **refund_verifications** - Auditoria de devoluciones
10. **bus_commands** - Comandos para dispositivos
11. **bus_locations** - Tracking GPS
12. **complaints** - Quejas de pasajeros

### Decisiones de Diseno Importantes

**1. Saldo en tarjeta, no en usuario:**
```php
// CORRECTO: El saldo esta en la tarjeta
$card->balance

// NO en el usuario pasajero
// Un pasajero puede tener multiples tarjetas
```

**2. Balance del conductor = ganancias:**
```php
// El conductor acumula ganancias
$driver->balance += $trip->fare;
```

**3. Auditoria completa:**
```php
// Cada pago genera:
// 1. Transaction - registro contable
// 2. PaymentEvent - auditoria tecnica
```

---

## SECCION 4: LOS TRES ROLES DEL SISTEMA

### ROL 1: ADMINISTRADOR

**Acceso:** Panel Web (`/admin`)

**Funcionalidades:**
- CRUD de usuarios (crear conductores, pasajeros)
- CRUD de tarjetas (asignar, recargar saldo)
- CRUD de buses (asignar a rutas)
- CRUD de rutas (definir tarifas)
- Monitoreo de viajes en tiempo real
- Gestion de transacciones
- Revision de quejas
- Control de devoluciones
- Dashboard con estadisticas

**Vistas principales:**
- `admin/dashboard` - Metricas generales
- `admin/users` - Gestion de usuarios
- `admin/cards` - Gestion de tarjetas
- `admin/monitoring/trips` - Viajes activos

### ROL 2: CONDUCTOR (DRIVER)

**Acceso:** App Movil (PWA)

**Funcionalidades:**
- Iniciar viaje (seleccionar bus y ruta)
- Finalizar viaje con reporte
- Ver transacciones del viaje actual
- Procesar devoluciones pendientes
- Ver solicitudes de pasajeros
- Actualizar ubicacion GPS

**Componente React:** `DriverDashboard.jsx`

**Endpoints principales:**
- `POST /api/driver/request-trip-start`
- `POST /api/driver/request-trip-end`
- `GET /api/driver/refund-requests`

### ROL 3: PASAJERO (PASSENGER)

**Acceso:** App Movil (PWA)

**Funcionalidades:**
- Consultar saldo de tarjeta
- Ver historial de transacciones
- Ver historial de viajes
- Solicitar devoluciones
- Crear quejas contra conductores
- Ver buses cercanos

**Componente React:** `PassengerDashboard.jsx`

**Endpoints principales:**
- `GET /api/profile`
- `GET /api/transactions`
- `POST /api/passenger/request-refund`

---

## SECCION 5: DECISIONES TECNICAS JUSTIFICADAS

### 1. PWA en lugar de App Nativa

**Decision:** Usar Progressive Web App

**Justificacion:**
- **Costo $0** - No requiere cuenta de Google Play ($25)
- **Desarrollo unico** - Mismo codigo web y movil
- **Actualizaciones instantaneas** - Sin pasar por App Store
- **Funciona offline** - Service Worker cachea recursos
- **Instalable** - Se agrega a pantalla de inicio

**Implementacion:**
```javascript
// public/manifest.json
{
  "name": "Interflow",
  "short_name": "Interflow",
  "display": "standalone",
  "theme_color": "#0891b2"
}

// public/service-worker.js
// Cachea recursos estaticos
```

### 2. Polling en lugar de WebSockets

**Decision:** El Arduino hace polling cada 3 segundos

**Justificacion:**
- **Simplicidad** - HTTP es mas simple que WebSocket en ESP8266
- **Confiabilidad** - No depende de conexion persistente
- **Recursos** - ESP8266 tiene RAM limitada
- **Latencia aceptable** - 3 segundos es suficiente para comandos

**Implementacion:**
```cpp
// Arduino: Cada 3 segundos
if (millis() - lastCheck > 3000) {
    checkServerForCommands();
}
```

### 3. Sanctum en lugar de Passport

**Decision:** Laravel Sanctum para autenticacion API

**Justificacion:**
- **Simplicidad** - Sin complejidad de OAuth2
- **Tokens SPA** - Ideal para React
- **Tokens API** - Para dispositivos IoT
- **Peso ligero** - Menos dependencias

**Implementacion:**
```php
// Login retorna token
return response()->json([
    'access_token' => $user->createToken('api')->plainTextToken,
    'user' => $user
]);
```

### 4. Vite en lugar de Webpack

**Decision:** Vite 7 como build tool

**Justificacion:**
- **Velocidad** - Hot Module Replacement instantaneo
- **Configuracion minima** - Sin webpack.config.js complejo
- **Soporte nativo ESM** - Modulos ES6 sin transpilacion
- **Integracion Laravel** - Plugin oficial laravel-vite-plugin

### 5. Tailwind CSS en lugar de Bootstrap

**Decision:** Tailwind CSS + Ionic Components

**Justificacion:**
- **Utility-first** - Clases atomicas, sin CSS custom
- **Tamano final** - Purge elimina CSS no usado
- **Consistencia** - Sistema de diseno unificado
- **Ionic** - Componentes moviles optimizados

---

## SECCION 6: SEGURIDAD IMPLEMENTADA

### Autenticacion
- Tokens Bearer con Laravel Sanctum
- Expiracion de tokens configurable
- Hash bcrypt para passwords

### Autorizacion
- Middleware `role:admin|driver|passenger`
- Validacion de propiedad de recursos
- Permisos por endpoint

### Proteccion CSRF
- Tokens CSRF en formularios Blade
- SameSite cookies

### Validacion de Datos
```php
$request->validate([
    'email' => 'required|email',
    'password' => 'required|min:6',
    'amount' => 'required|numeric|min:0'
]);
```

### Transacciones Atomicas
```php
DB::transaction(function() {
    // Descuenta saldo
    // Incrementa ganancia
    // Registra transaccion
    // Si falla uno, revierte todo
});
```

---

## SECCION 7: PREGUNTAS FRECUENTES DE DEFENSA

### P: Por que no usar NFC del celular en lugar de tarjetas?
**R:** Las tarjetas RFID son mas economicas ($0.50 c/u), no requieren smartphone, y funcionan sin bateria. Ademas, no todos los celulares tienen NFC.

### P: Que pasa si no hay internet en el bus?
**R:** El Arduino almacena el trip_id localmente. Cuando recupera conexion, procesa los pagos pendientes. Los comandos se encolan en la base de datos.

### P: Como previenen el fraude?
**R:** Multiples capas:
1. UID de tarjeta es unico e inmodificable
2. Cada transaccion tiene auditoria completa
3. Sistema de devoluciones requiere aprobacion
4. Logs de PaymentEvents para investigacion

### P: Escalabilidad del sistema?
**R:** El sistema soporta:
- Multiples buses simultaneos
- Base de datos MySQL escalable
- API RESTful stateless
- Railway escala automaticamente

### P: Por que no usar QR en lugar de RFID?
**R:** RFID es mas rapido (100ms vs 2-3s), no requiere que pasajero abra app, funciona en condiciones de poca luz, y es mas dificil de falsificar.

---

## SECCION 8: DEMO SUGERIDA (10 minutos)

### Minuto 1-2: Panel Admin
1. Login como admin
2. Mostrar dashboard con estadisticas
3. Crear un usuario pasajero nuevo

### Minuto 3-4: Recarga de Tarjeta
1. Ir a seccion de tarjetas
2. Buscar tarjeta del pasajero
3. Recargar Bs. 50
4. Mostrar historial de recargas

### Minuto 5-6: Conductor Inicia Viaje
1. Login como conductor en app movil
2. Seleccionar bus y ruta
3. Iniciar viaje
4. Mostrar que el comando llega al Arduino

### Minuto 7-8: Pago con Tarjeta
1. Acercar tarjeta RFID al lector
2. Mostrar mensaje de exito en Arduino
3. Ver transaccion en panel de conductor
4. Verificar descuento en saldo de pasajero

### Minuto 9-10: Pasajero Consulta
1. Login como pasajero en app movil
2. Ver saldo actualizado
3. Ver transaccion reciente
4. Mostrar opcion de devolucion

---

## SECCION 9: MEJORAS FUTURAS

### Corto Plazo
- Notificaciones push para pasajeros
- App nativa con Capacitor
- Reportes exportables a PDF/Excel

### Mediano Plazo
- Integracion con pasarelas de pago (QR para recargas)
- Sistema de tarifas dinamicas (hora pico)
- Mapa en tiempo real de buses

### Largo Plazo
- Machine learning para prediccion de demanda
- Reconocimiento facial como alternativa a RFID
- Integracion con sistemas municipales

---

## SECCION 10: METRICAS DEL PROYECTO

### Lineas de Codigo (aproximado)
- PHP (Laravel): ~5,000 LOC
- JavaScript (React): ~2,500 LOC
- CSS (Tailwind): ~500 LOC
- Arduino C++: ~300 LOC
- **Total: ~8,300 LOC**

### Archivos
- Controladores: 25
- Modelos: 12
- Migraciones: 31
- Componentes React: 6
- Vistas Blade: ~50

### Base de Datos
- Tablas: 12
- Relaciones: 20+

### Endpoints API
- Publicos: 7
- Protegidos: 25+
- **Total: 32+ endpoints**

---

## SECCION 11: AGRADECIMIENTOS Y CIERRE

Este proyecto representa meses de trabajo y aprendizaje. Las tecnologias utilizadas (Laravel, React, Arduino) son ampliamente usadas en la industria, lo que facilita:

1. **Mantenibilidad** - Codigo estandar y documentado
2. **Escalabilidad** - Arquitectura preparada para crecer
3. **Empleabilidad** - Tecnologias demandadas en el mercado

El sistema INTERFLOW demuestra capacidad para:
- Disenar arquitecturas de software complejas
- Integrar hardware con software
- Implementar sistemas de tiempo real
- Aplicar mejores practicas de seguridad

---

**Mucha suerte en tu defensa, Brandon!**

*"El codigo es poesia que las maquinas pueden leer."*

# FLUJOS PRINCIPALES DEL SISTEMA INTERFLOW

## Documento Preparatorio para Defensa Final

**Proyecto:** Sistema de Cobro de Transporte Publico INTERFLOW
**Desarrollador:** Brandon
**Fecha:** Noviembre 2025

---

## 1. FLUJO DE REGISTRO DE VIAJE

### Descripcion
El conductor inicia su jornada laboral registrando un nuevo viaje en el sistema.

### Actores
- **Conductor** (Usuario con rol driver)
- **Sistema Backend** (Laravel API)
- **Dispositivo IoT** (Arduino ESP8266)

### Pasos Detallados

```
CONDUCTOR                    BACKEND                      ARDUINO
    |                           |                           |
    | 1. Abre app movil         |                           |
    |-------------------------->|                           |
    |                           |                           |
    | 2. Selecciona bus         |                           |
    |    y ruta                 |                           |
    |-------------------------->|                           |
    |                           |                           |
    | 3. Click "Iniciar Viaje"  |                           |
    |-------------------------->|                           |
    |                           |                           |
    |                    4. Valida conductor               |
    |                       y bus disponible               |
    |                           |                           |
    |                    5. Crea registro Trip             |
    |                       con inicio=now()               |
    |                           |                           |
    |                    6. Crea BusCommand                |
    |                       comando=start_trip             |
    |                           |                           |
    |                           |   7. Polling cada 3s     |
    |                           |<--------------------------|
    |                           |                           |
    |                           |   8. Retorna comando     |
    |                           |-------------------------->|
    |                           |                           |
    |                           |   9. Arduino activa      |
    |                           |      modo COBRANDO       |
    |                           |                           |
    |                           |   10. Confirma comando   |
    |                           |<--------------------------|
    |                           |                           |
    | 11. Muestra "Viaje        |                           |
    |     Activo" en app        |                           |
    |<--------------------------|                           |
    |                           |                           |
```

### Tablas Involucradas
- `trips` - Registro del viaje
- `buses` - Bus asignado
- `rutas` - Ruta del viaje
- `bus_commands` - Comando para dispositivo

### Endpoints API
- `POST /api/driver/request-trip-start`
- `GET /api/device/command/{bus_id}`
- `POST /api/device/command/{id}/complete`

---

## 2. FLUJO DE PROCESAMIENTO DE PAGO

### Descripcion
Un pasajero acerca su tarjeta RFID al lector y el sistema procesa el cobro automaticamente.

### Actores
- **Pasajero** (Usuario con tarjeta RFID)
- **Arduino** (Lector RFID + WiFi)
- **Backend** (Laravel API)

### Pasos Detallados

```
PASAJERO                     ARDUINO                      BACKEND
    |                           |                           |
    | 1. Acerca tarjeta         |                           |
    |   RFID al lector          |                           |
    |-------------------------->|                           |
    |                           |                           |
    |                    2. Lee UID de tarjeta             |
    |                       (ej: "A1B2C3D4")               |
    |                           |                           |
    |                           | 3. POST /api/payment/     |
    |                           |    process                |
    |                           |    {uid, trip_id}         |
    |                           |-------------------------->|
    |                           |                           |
    |                           |                    4. Busca tarjeta
    |                           |                       por UID
    |                           |                           |
    |                           |                    5. Valida:
    |                           |                       - Tarjeta activa
    |                           |                       - Saldo >= tarifa
    |                           |                       - Trip activo
    |                           |                           |
    |                           |                    6. Descuenta saldo
    |                           |                       card.balance -= fare
    |                           |                           |
    |                           |                    7. Incrementa ganancia
    |                           |                       driver.balance += fare
    |                           |                           |
    |                           |                    8. Crea Transaction
    |                           |                       type='fare'
    |                           |                           |
    |                           |                    9. Crea PaymentEvent
    |                           |                       event_type='success'
    |                           |                           |
    |                           | 10. Response:             |
    |                           |     {status: "success",   |
    |                           |      new_balance: X}      |
    |                           |<--------------------------|
    |                           |                           |
    |                    11. LED verde / Beep              |
    | 12. Pago exitoso          |    confirmacion          |
    |<--------------------------|                           |
    |                           |                           |
```

### Validaciones de Seguridad
1. **Tarjeta existe** - Busqueda por UID unico
2. **Tarjeta activa** - Campo `active = true`
3. **Saldo suficiente** - `balance >= tarifa_ruta`
4. **Viaje activo** - `trip.fin IS NULL`
5. **Tarifa correcta** - Obtenida de la ruta del viaje

### Tablas Involucradas
- `cards` - Saldo del pasajero (FUENTE DE VERDAD)
- `transactions` - Registro del pago
- `payment_events` - Auditoria del evento
- `trips` - Viaje actual
- `users` - Balance del conductor

### Endpoints API
- `POST /api/payment/process`

### Respuestas Posibles

**Exito:**
```json
{
  "status": "success",
  "message": "Pago procesado correctamente",
  "new_balance": "15.50",
  "fare": "2.50"
}
```

**Saldo Insuficiente:**
```json
{
  "status": "error",
  "message": "Saldo insuficiente",
  "available": "1.50",
  "required": "2.50"
}
```

---

## 3. FLUJO DE CONSULTA DE SALDO

### Descripcion
El pasajero consulta su saldo disponible y historial de transacciones desde la app movil.

### Actores
- **Pasajero** (App movil)
- **Backend** (Laravel API)

### Pasos Detallados

```
PASAJERO                                 BACKEND
    |                                       |
    | 1. Abre app movil                     |
    |    (ya autenticado)                   |
    |                                       |
    | 2. GET /api/profile                   |
    |-------------------------------------->|
    |                                       |
    |                                3. Obtiene usuario
    |                                   con tarjetas
    |                                       |
    | 4. Response:                          |
    |    {user, cards: [{balance}]}         |
    |<--------------------------------------|
    |                                       |
    | 5. Muestra saldo en pantalla          |
    |    "Bs. 25.50"                        |
    |                                       |
    | 6. GET /api/transactions              |
    |-------------------------------------->|
    |                                       |
    |                                7. Lista transacciones
    |                                   de sus tarjetas
    |                                       |
    | 8. Response:                          |
    |    [{type, amount, date}...]          |
    |<--------------------------------------|
    |                                       |
    | 9. Muestra historial                  |
    |                                       |
```

### Datos Mostrados
- **Saldo actual** - Suma de balance de todas sus tarjetas
- **Ultimas transacciones** - Tipo, monto, fecha, ruta
- **Historial de viajes** - Fecha, ruta, bus, conductor

### Endpoints API
- `GET /api/profile` - Datos del usuario y tarjetas
- `GET /api/transactions` - Historial de transacciones
- `GET /api/trips` - Historial de viajes

---

## 4. FLUJO DE DEVOLUCION

### Descripcion
Un pasajero solicita devolucion de un cobro incorrecto, el conductor verifica y procesa.

### Actores
- **Pasajero** (Solicita)
- **Conductor** (Verifica)
- **Sistema** (Procesa)

### Pasos Detallados

```
PASAJERO              CONDUCTOR              BACKEND
    |                     |                     |
    | 1. Identifica cobro |                     |
    |    incorrecto       |                     |
    |                     |                     |
    | 2. Solicita         |                     |
    |    devolucion       |                     |
    |-------------------------------------------->|
    |                     |                     |
    |                     |              3. Crea RefundRequest
    |                     |                 status='pending'
    |                     |                 genera token
    |                     |                     |
    |                     | 4. Notificacion     |
    |                     |    nueva solicitud  |
    |                     |<--------------------|
    |                     |                     |
    |                     | 5. Revisa detalles  |
    |                     |    tarjeta UID,     |
    |                     |    monto, hora      |
    |                     |                     |
    |                     | 6. Aprueba/Rechaza  |
    |                     |-------------------->|
    |                     |                     |
    |                     |              7. Si aprobado:
    |                     |                 - Incrementa saldo
    |                     |                 - Descuenta ganancia
    |                     |                 - Crea transaccion
    |                     |                     |
    | 8. Notificacion     |                     |
    |    resultado        |                     |
    |<-----------------------------------------|
    |                     |                     |
```

### Estados de Devolucion
1. `pending` - Esperando verificacion del conductor
2. `verified` - Pasajero confirmo por email
3. `completed` - Devolucion procesada
4. `rejected` - Conductor rechazo
5. `cancelled` - Pasajero cancelo

### Tablas Involucradas
- `refund_requests` - Solicitud de devolucion
- `refund_verifications` - Auditoria de acciones
- `transactions` - Nueva transaccion type='refund'
- `cards` - Incremento de saldo
- `users` - Decremento de ganancia conductor

### Endpoints API
- `POST /api/passenger/request-refund` - Solicitar
- `GET /api/driver/refund-requests` - Listar pendientes
- `POST /api/driver/approve-refund/{id}` - Aprobar/Rechazar

---

## 5. FLUJO DE FINALIZACION DE VIAJE

### Descripcion
El conductor finaliza su viaje y genera reporte de la jornada.

### Pasos Detallados

```
CONDUCTOR                    BACKEND                      ARDUINO
    |                           |                           |
    | 1. Click "Finalizar       |                           |
    |    Viaje"                 |                           |
    |-------------------------->|                           |
    |                           |                           |
    | 2. Escribe reporte        |                           |
    |    (novedades, obs.)      |                           |
    |-------------------------->|                           |
    |                           |                           |
    |                    3. Actualiza Trip                 |
    |                       fin=now()                      |
    |                       reporte=texto                  |
    |                           |                           |
    |                    4. Crea BusCommand                |
    |                       comando=end_trip               |
    |                           |                           |
    |                           |   5. Polling             |
    |                           |<--------------------------|
    |                           |                           |
    |                           |   6. Retorna comando     |
    |                           |-------------------------->|
    |                           |                           |
    |                           |   7. Arduino vuelve a    |
    |                           |      modo ESPERANDO      |
    |                           |                           |
    | 8. Muestra resumen:       |                           |
    |    - Total pasajeros      |                           |
    |    - Total recaudado      |                           |
    |    - Devoluciones         |                           |
    |<--------------------------|                           |
    |                           |                           |
```

### Datos del Resumen
- Total de transacciones del viaje
- Monto total recaudado
- Numero de pasajeros
- Devoluciones procesadas
- Incidentes reportados

### Endpoints API
- `POST /api/driver/request-trip-end`
- `POST /api/driver/update-trip-report`

---

## RESUMEN DE FLUJOS

| Flujo | Complejidad | Tablas | Endpoints |
|-------|-------------|--------|-----------|
| Registro de Viaje | Media | 4 | 3 |
| Procesamiento de Pago | Alta | 5 | 1 |
| Consulta de Saldo | Baja | 2 | 3 |
| Devolucion | Alta | 5 | 3 |
| Finalizacion de Viaje | Media | 3 | 2 |

---

## DIAGRAMAS ADICIONALES PARA PRESENTACION

### Arquitectura General

```
+------------------+     +------------------+     +------------------+
|   APP MOVIL      |     |    BACKEND       |     |    HARDWARE      |
|  (React/PWA)     |<--->|   (Laravel)      |<--->| (Arduino ESP8266)|
+------------------+     +------------------+     +------------------+
                               |
                               v
                         +------------------+
                         |    MySQL DB      |
                         |   (Railway)      |
                         +------------------+
```

### Comunicacion de Componentes

```
Pasajero App  ----HTTPS---->  Laravel API  ----MySQL--->  Database
                                   ^
Conductor App ----HTTPS---->       |
                                   |
Arduino       ----HTTP/Poll---->   |
```

---

**Este documento sirve como guia para explicar los flujos principales durante la defensa del proyecto.**

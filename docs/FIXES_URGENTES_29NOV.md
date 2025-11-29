# FIXES URGENTES - 29 Noviembre 2025

## 🔴 PROBLEMA 1: Brandon (estudiante) cobra 2.30 Bs en lugar de 1.00 Bs

### Posibles Causas:
1. **El usuario Brandon no tiene `user_type = 'student_university'` en producción**
2. **Railway no tiene el código actualizado con calculateFare()**
3. **La tarjeta está asociada a otro usuario**

### Query para verificar:
```sql
SELECT id, name, email, role, user_type, school_name, university_name
FROM users
WHERE name LIKE '%Brandon%';
```

### Solución si user_type es incorrecto:
```sql
UPDATE users
SET user_type = 'student_university',
    university_name = 'UMSA'
WHERE name LIKE '%Brandon%';
```

### Verificar que Railway tiene el código actualizado:
- Commit con fix: `4b25766 - fix: Corregir tarifas de cobro según tipo de usuario`
- Railway debe tener este commit deployed

---

## 🔴 PROBLEMA 2: Error 404 en `/api/driver/current-trip-status`

### Causa:
La ruta NO existe en `routes/api.php`

### Solución:
Agregar la ruta faltante:

```php
// En routes/api.php
Route::get('/driver/current-trip-status', [TripController::class, 'currentTripStatus']);
```

**NOTA:** Esta ruta ya debería existir según el código. Verificar que Railway tenga el último código.

---

## 🔴 PROBLEMA 3: Error en Modal de Horas - "Unexpected token '<'"

### Causa:
El endpoint `/api/driver/time-records/turno` retorna HTML en lugar de JSON (error 401/403)

### Posibles Causas:
1. **La migración `driver_time_records` NO se ejecutó en Railway**
2. **Middleware de autenticación falla**
3. **La tabla no existe**

### Solución:
1. Ejecutar migración en Railway:
```bash
railway run php artisan migrate
```

2. Verificar que la tabla existe:
```sql
SHOW TABLES LIKE 'driver_time_records';
```

3. Si no existe, ejecutar migración manualmente:
```sql
CREATE TABLE driver_time_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    turno_id INT,
    trip_ida_id INT,
    trip_vuelta_id INT,
    inicio_ida DATETIME,
    fin_ida DATETIME,
    inicio_vuelta DATETIME,
    fin_vuelta_estimado DATETIME,
    fin_vuelta_real DATETIME,
    estado ENUM('en_curso', 'normal', 'retrasado'),
    tiempo_retraso_minutos INT,
    es_ultimo_viaje BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE SET NULL,
    FOREIGN KEY (trip_ida_id) REFERENCES trips(id) ON DELETE SET NULL,
    FOREIGN KEY (trip_vuelta_id) REFERENCES trips(id) ON DELETE SET NULL
);
```

---

## 🔴 PROBLEMA 4: Error 401 en `/api/driver/time-records/turno`

### Causa:
Problema de autenticación - el token del driver no se está enviando correctamente

### Verificar en HorasModal.jsx:
```javascript
const token = localStorage.getItem('token'); // ← ¿Es correcto?
```

Debería ser:
```javascript
const token = localStorage.getItem('driver_token'); // ← Token del driver
```

---

## ✅ ACCIONES INMEDIATAS

### 1. Verificar en MySQL (Railway):
```sql
-- Ver usuario Brandon
SELECT id, name, role, user_type FROM users WHERE name LIKE '%Brandon%';

-- Ver si existe tabla driver_time_records
SHOW TABLES LIKE 'driver_time_records';

-- Ver rutas en la tabla
SELECT id, nombre, tarifa_base FROM rutas WHERE id = 79;
```

### 2. Ejecutar migración:
```bash
railway run php artisan migrate
```

### 3. Corregir user_type de Brandon (si es necesario):
```sql
UPDATE users
SET user_type = 'student_university'
WHERE id = <ID_DE_BRANDON>;
```

### 4. Verificar deployment en Railway:
- Ir a https://railway.app
- Ver logs del último deploy
- Confirmar que el commit `9ad3e36` está deployed

---

## 📋 QUERIES ÚTILES PARA DEBUGGING

### Ver último pago de Brandon:
```sql
SELECT t.id, t.amount, t.type, t.description, t.created_at,
       u.name as usuario, u.user_type
FROM transactions t
JOIN cards c ON t.card_id = c.id
JOIN users u ON c.user_id = u.id
WHERE u.name LIKE '%Brandon%'
ORDER BY t.created_at DESC
LIMIT 5;
```

### Ver eventos de pago:
```sql
SELECT pe.id, pe.amount, pe.message, pe.event_type, pe.created_at,
       u.name as pasajero, u.user_type
FROM payment_events pe
JOIN users u ON pe.passenger_id = u.id
WHERE u.name LIKE '%Brandon%'
ORDER BY pe.created_at DESC
LIMIT 5;
```

### Ver tarifa de la ruta:
```sql
SELECT id, nombre, tarifa_base, tarifa_adulto, tarifa_descuento
FROM rutas
WHERE id = 79;
```

---

## 🎯 RESULTADO ESPERADO

Después de aplicar los fixes:

1. **Brandon cobra 1.00 Bs** ✅
2. **Modal de Horas funciona** ✅
3. **No hay errores 404** ✅
4. **No hay errores de autenticación** ✅

---

**IMPORTANTE:** Ejecutar primero los queries de verificación antes de hacer UPDATE.

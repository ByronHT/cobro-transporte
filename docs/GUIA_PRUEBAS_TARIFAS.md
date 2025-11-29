# GUÍA DE PRUEBAS - Verificación de Tarifas

**Objetivo:** Verificar que el sistema aplica correctamente las tarifas según el tipo de usuario

---

## ✅ CÓDIGO YA IMPLEMENTADO

El método `calculateFare()` ya está implementado en:
- **Archivo:** `app/Http/Controllers/API/DriverActionController.php`
- **Líneas:** 205-216

### Lógica de Tarifas:
```
- student_school      → 1.00 Bs
- student_university  → 1.00 Bs
- minor              → 1.00 Bs
- senior             → 1.00 Bs
- adult              → 2.30 Bs (tarifa completa)
```

---

## 📋 PRUEBAS QUE DEBES REALIZAR

### PRUEBA 1: Verificar usuarios en la base de datos

Ejecuta en Railway:
```bash
railway run php artisan tinker
```

Dentro de tinker ejecuta:
```php
// Ver total de usuarios
User::count();

// Ver usuarios por tipo
User::select('user_type', DB::raw('count(*) as total'))
    ->groupBy('user_type')
    ->get();

// Ver un usuario estudiante específico (si existe)
User::where('user_type', 'student_school')->first();
```

**Resultado esperado:**
- Debes tener al menos un usuario de tipo `student_school`, `student_university`, `minor`, o `senior` para probar

---

### PRUEBA 2: Verificar que el build está actualizado

1. **Compilar el código:**
```bash
npm run build
```

2. **Sincronizar con Android:**
```bash
npx cap sync android
```

3. **Verificar la fecha del build:**
```bash
# Ver archivos generados
dir public\build\assets
```

**Resultado esperado:**
- Los archivos deben tener fecha/hora reciente (hoy o ayer)

---

### PRUEBA 3: Crear usuario de prueba estudiante

En Railway tinker:
```php
$user = User::create([
    'name' => 'Estudiante Prueba',
    'email' => 'estudiante@test.com',
    'password' => bcrypt('123456'),
    'ci' => '1234567',
    'role' => 'passenger',
    'user_type' => 'student_school',
    'school_name' => 'Colegio de Prueba',
    'birth_date' => '2008-01-01',
    'active' => true
]);

// Crear tarjeta para el usuario
$card = Card::create([
    'user_id' => $user->id,
    'uid' => 'TEST12345',
    'balance' => 10.00,
    'active' => true
]);

echo "Usuario creado - ID: " . $user->id . "\n";
echo "Tarjeta creada - UID: " . $card->uid . "\n";
```

---

### PRUEBA 4: Crear viaje activo de prueba

En Railway tinker:
```php
// Buscar un chofer
$driver = User::where('role', 'driver')->first();

// Buscar un bus
$bus = Bus::first();

// Buscar una ruta
$ruta = Ruta::first();

// Crear viaje activo
$trip = Trip::create([
    'driver_id' => $driver->id,
    'bus_id' => $bus->id,
    'ruta_id' => $ruta->id,
    'tipo_viaje' => 'ida',
    'inicio' => now(),
    'fin' => null // NULL = viaje activo
]);

echo "Viaje creado - ID: " . $trip->id . "\n";
```

---

### PRUEBA 5: Probar cobro con Postman o Insomnia

**Endpoint:** `POST https://cobro-transporte-production-dac4.up.railway.app/api/driver/process-payment`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {TOKEN_DEL_CHOFER}
```

**Body (JSON):**
```json
{
    "card_id": 1,
    "trip_id": 1
}
```

**Resultado esperado:**
```json
{
    "message": "Pago procesado exitosamente.",
    "amount_charged": 1.00,
    "user_type": "student_school",
    "card_new_balance": 9.00,
    "driver_new_balance": 1.00
}
```

**⚠️ IMPORTANTE:**
- `amount_charged` debe ser **1.00** para estudiantes
- `amount_charged` debe ser **2.30** para adultos

---

### PRUEBA 6: Verificar en la app móvil real

1. **Instalar APK actualizado** en el dispositivo Android
2. **Iniciar sesión como chofer**
3. **Iniciar un viaje**
4. **Acercar tarjeta RFID de un estudiante**
5. **Verificar notificación:** Debe decir "Cobro: 1.00 Bs (student_school)"

---

### PRUEBA 7: Revisar logs de Railway

```bash
railway logs --tail 100
```

Busca líneas que contengan:
- `"Pago procesado"`
- `"amount_charged"`
- Errores relacionados con `calculateFare`

---

## 🔍 VERIFICACIONES ADICIONALES

### Verificar transacciones en base de datos:

En Railway tinker:
```php
// Ver últimas 5 transacciones
Transaction::with('card.user')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get()
    ->each(function($t) {
        echo "Monto: " . $t->amount . " Bs - Tipo: " . $t->card->user->user_type . "\n";
    });
```

**Resultado esperado:**
- Las transacciones de estudiantes deben tener `amount = 1.00`
- Las transacciones de adultos deben tener `amount = 2.30`

---

### Verificar eventos de pago:

```php
PaymentEvent::with('passenger')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get()
    ->each(function($e) {
        echo "Mensaje: " . $e->message . " - Amount: " . $e->amount . "\n";
    });
```

---

## ❌ PROBLEMAS COMUNES

### Problema 1: Aún cobra 2.30 Bs a estudiantes

**Causa:** El build de la app no está actualizado

**Solución:**
```bash
npm run build
npx cap sync android
# Reinstalar APK en el dispositivo
```

---

### Problema 2: Error "user_type is null"

**Causa:** Usuarios creados antes de la migración no tienen `user_type`

**Solución:**
```php
// Actualizar usuarios sin user_type
User::whereNull('user_type')->update(['user_type' => 'adult']);
```

---

### Problema 3: Arduino envía "amount" en el request

**Causa:** El código del Arduino aún envía el monto

**Solución:**
- Editar código de Arduino
- Eliminar parámetro `amount` del JSON
- Solo enviar `card_id` y `trip_id`

---

## 📊 CHECKLIST DE VERIFICACIÓN

Marca cada item cuando lo completes:

- [ ] Código `calculateFare()` existe en DriverActionController (línea 205-216)
- [ ] Migración `add_login_code_and_ci_to_users_table` ejecutada en Railway
- [ ] Al menos 1 usuario de prueba con `user_type = student_school`
- [ ] Al menos 1 tarjeta con saldo >= 2.00 Bs
- [ ] Build compilado (`npm run build`)
- [ ] Capacitor sincronizado (`npx cap sync android`)
- [ ] Viaje activo creado para pruebas
- [ ] Endpoint probado con Postman/Insomnia
- [ ] `amount_charged = 1.00` para estudiantes ✅
- [ ] `amount_charged = 2.30` para adultos ✅
- [ ] Transacciones guardadas con monto correcto
- [ ] PaymentEvents con mensaje correcto
- [ ] Notificaciones en app móvil muestran monto correcto

---

## 🎯 RESULTADO FINAL ESPERADO

Cuando TODO esté correcto:

| Tipo de Usuario | Tarifa Cobrada | Estado |
|----------------|---------------|---------|
| student_school | 1.00 Bs | ✅ |
| student_university | 1.00 Bs | ✅ |
| minor | 1.00 Bs | ✅ |
| senior | 1.00 Bs | ✅ |
| adult | 2.30 Bs | ✅ |

---

**NOTA:** Si después de todas estas pruebas sigue cobrando mal, avísame para revisar más a fondo el código.

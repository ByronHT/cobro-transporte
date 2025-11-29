# PENDIENTES: Corrección de Tarifas de Cobro

**Fecha:** 27 de Noviembre de 2025
**Prioridad:** ALTA
**Tiempo estimado:** 2-3 horas

---

## 🔴 PROBLEMA IDENTIFICADO

### Síntoma:
- Usuario tipo **estudiante** debería pagar **1.00 Bs**
- Pero se está cobrando **2.30 Bs**
- Las notificaciones y registros no coinciden con el cobro real

### Causa Raíz:
El método `processPayment()` en `DriverActionController.php` **NO valida** el tipo de usuario para aplicar la tarifa correcta. Solo recibe el `amount` del Arduino/dispositivo y lo cobra sin verificar.

---

## ✅ SOLUCIÓN A IMPLEMENTAR

### 1. Modificar `app/Http/Controllers/API/DriverActionController.php`

**Línea 121-175:** Método `processPayment()`

**Cambios requeridos:**

```php
public function processPayment(Request $request)
{
    $request->validate([
        'card_id' => 'required|integer|exists:cards,id',
        'trip_id' => 'required|integer|exists:trips,id',
        // ELIMINAR: 'amount' del request (ahora se calcula en backend)
    ]);

    // 1. Obtener tarjeta y usuario
    $card = Card::with('user')->findOrFail($request->card_id);
    $user = $card->user;

    // 2. Obtener viaje y ruta
    $trip = Trip::with('ruta')->where('id', $request->trip_id)
                ->whereNull('fin')
                ->firstOrFail();

    // 3. CALCULAR TARIFA CORRECTA según tipo de usuario
    $amount = $this->calculateFare($user, $trip->ruta);

    // 4. Validar saldo
    if ($card->balance < $amount) {
        return response()->json(['message' => 'Saldo insuficiente'], 400);
    }

    // 5. Procesar cobro
    $card->balance -= $amount;
    $card->save();

    // 6. Crear transacción
    $transaction = Transaction::create([
        'trip_id' => $trip->id,
        'card_id' => $card->id,
        'driver_id' => $trip->driver_id,
        'bus_id' => $trip->bus_id,
        'ruta_id' => $trip->ruta_id,
        'amount' => $amount,
        'type' => 'fare',
        'description' => "Pasaje - {$user->user_type}",
    ]);

    // 7. Actualizar balance del chofer
    $driver = $trip->driver;
    $driver->balance += $amount;
    $driver->save();

    // 8. Crear notificación con monto correcto
    \App\Models\PaymentEvent::create([
        'trip_id' => $trip->id,
        'card_id' => $card->id,
        'card_uid' => $card->uid,
        'passenger_id' => $user->id,
        'amount' => $amount,
        'event_type' => 'payment',
        'message' => "Cobro procesado: {$amount} Bs ({$user->user_type})"
    ]);

    return response()->json([
        'message' => 'Pago procesado',
        'amount_charged' => $amount,
        'user_type' => $user->user_type,
        'card_new_balance' => $card->balance,
        'driver_new_balance' => $driver->balance,
    ], 201);
}

// NUEVO MÉTODO: Calcular tarifa según tipo de usuario
private function calculateFare($user, $ruta)
{
    // Tipos con descuento: TODOS pagan 1.00 Bs
    $discountedTypes = ['senior', 'minor', 'student_school', 'student_university'];

    if (in_array($user->user_type, $discountedTypes)) {
        return 1.00; // TARIFA ÚNICA PARA TODOS
    }

    // Adulto regular
    return $ruta->tarifa_base ?? 2.30;
}
```

---

## 📋 PASOS A SEGUIR (EN ORDEN)

### Paso 1: Modificar DriverActionController.php
- [ ] Abrir `app/Http/Controllers/API/DriverActionController.php`
- [ ] Reemplazar método `processPayment()` (líneas 121-175)
- [ ] Agregar método privado `calculateFare()`

### Paso 2: Actualizar validación de request
- [ ] Eliminar `'amount' => 'required|numeric'` de la validación
- [ ] El amount ahora se calcula en backend, no viene del request

### Paso 3: Agregar PaymentEvent
- [ ] Verificar que existe modelo `PaymentEvent`
- [ ] Crear notificación con monto correcto

### Paso 4: Compilar y sincronizar
```bash
npm run build
npx cap sync android
```

### Paso 5: Probar
- [ ] Crear usuario tipo `student_school`
- [ ] Asignar tarjeta con saldo 10 Bs
- [ ] Hacer cobro desde app chofer
- [ ] Verificar que se cobró **1.00 Bs** (no 2.30 Bs)
- [ ] Verificar notificación muestra monto correcto
- [ ] Verificar registro en transacciones

---

## 🔍 VALIDACIONES ADICIONALES

### Verificar en Base de Datos:
```sql
-- Ver tipos de usuario y sus tarifas
SELECT user_type, COUNT(*) as cantidad
FROM users
WHERE user_type IS NOT NULL
GROUP BY user_type;

-- Ver última transacción
SELECT t.*, u.user_type, u.name
FROM transactions t
JOIN cards c ON t.card_id = c.id
JOIN users u ON c.user_id = u.id
ORDER BY t.created_at DESC
LIMIT 5;
```

---

## 📝 NOTAS IMPORTANTES

1. **NO modificar Arduino/dispositivo** - El problema está en el backend
2. **Tarifa única:** Todos los tipos especiales pagan 1.00 Bs
3. **Solo adultos regulares** pagan tarifa_base (2.30 Bs)
4. **Notificaciones:** Deben mostrar el monto REAL cobrado
5. **Registros:** transaction.amount debe reflejar lo cobrado

---

## 🎯 RESULTADO ESPERADO

**Antes:**
- Estudiante → Se cobra 2.30 Bs ❌
- Notificación dice 2.30 Bs ❌
- Registro muestra 2.30 Bs ❌

**Después:**
- Estudiante → Se cobra 1.00 Bs ✅
- Notificación dice 1.00 Bs ✅
- Registro muestra 1.00 Bs ✅
- Mayor/Menor/Universitario → 1.00 Bs ✅
- Adulto regular → 2.30 Bs ✅

---

**Prioridad:** 🔴 URGENTE - Implementar mañana temprano
**Tiempo:** ~2 horas
**Complejidad:** Media

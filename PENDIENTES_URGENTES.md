# PENDIENTES URGENTES - Sistema de Transporte

**Fecha:** 27 de Noviembre de 2025
**Prioridad:** ALTA
**Estado:** Pendiente de implementación

---

## 🔴 PROBLEMA 1: Tarifas no se aplican correctamente

### Síntoma:
- Aunque se implementó `calculateFare()`, las tarifas NO se están aplicando
- Posible causa: React no sincronizado con últimos cambios del backend
- O alguna función en el proyecto está fallando

### Solución:
1. Verificar logs de Laravel para ver errores
2. Probar endpoint directamente: `POST /api/driver/process-payment`
3. Revisar si el Arduino/dispositivo está enviando los parámetros correctos
4. Verificar que la app tenga los últimos cambios del backend

---

## 🟡 FUNCIONALIDAD 2: Sistema de Control de Horas (Chofer)

### Ubicación:
**DriverDashboard** - Vista principal del chofer

### Requerimiento:
Agregar botón **"Horas"** que muestre tabla de registro de tiempos

### Estructura de la Tabla:

| Columna IDA | Columna VUELTA | Estado | Retraso |
|-------------|----------------|--------|---------|
| Inicio viaje IDA (fecha/hora) | Fin viaje IDA (fecha/hora) | Normal/Retrasado | Tiempo retrasado |
| Inicio viaje VUELTA (fecha/hora) | Fin estimado viaje VUELTA | Normal/Retrasado | Tiempo retrasado |

### Lógica del Sistema:

#### 1. **Inicio de Viaje IDA**
```
Acción: Chofer inicia viaje IDA
Registro:
  - columna_ida = "2025-11-27 14:30:00" (fecha y hora de inicio)
  - columna_vuelta = null
  - estado = "en_curso"
```

#### 2. **Fin de Viaje IDA**
```
Acción: Chofer concluye viaje IDA
Registro:
  - columna_vuelta = "2025-11-27 15:15:00" (fecha y hora de fin)
  - calcular_tiempo_ida = vuelta - ida = 45 minutos
  - estado = "normal" o "retrasado"
```

#### 3. **Inicio de Viaje VUELTA**
```
Acción: Chofer inicia viaje VUELTA
Nuevo registro en tabla:
  - columna_ida = "2025-11-27 15:20:00" (inicio vuelta)
  - columna_vuelta = calcular hora estimada de llegada
  - estado = "en_curso"

Nota: Si cambió de bus, igual se mantiene el registro
```

#### 4. **Fin de Viaje VUELTA**
```
Acción: Chofer concluye viaje VUELTA
Registro:
  - columna_vuelta = hora real de llegada
  - comparar con hora estimada
  - si llegó_tarde: estado = "retrasado", retraso = "15 min"
  - si llegó_a_tiempo: estado = "normal", retraso = null
```

### Cálculo de Retrasos:

```php
// Tiempo promedio de viaje (configurar por ruta)
$tiempo_estimado_ida = 45; // minutos
$tiempo_estimado_vuelta = 45; // minutos

// Al finalizar viaje
$tiempo_real = $hora_fin - $hora_inicio;
$diferencia = $tiempo_real - $tiempo_estimado;

if ($diferencia > 5) { // tolerancia de 5 minutos
    $estado = "retrasado";
    $retraso = "{$diferencia} min";
} else {
    $estado = "normal";
    $retraso = null;
}
```

### Botón "Último Viaje":

Agregar en formulario de conclusión de viaje:

```
[ ] Marcar como último viaje del día

Si se marca:
  - Si es viaje IDA: registrar solo datos de IDA
  - Si es viaje VUELTA: registrar datos de VUELTA
  - Limpiar tabla de horas para el próximo día
  - Guardar resumen del día en tabla histórica
```

### Tabla de Base de Datos:

```sql
CREATE TABLE driver_time_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    turno_id INT,
    trip_ida_id INT,
    trip_vuelta_id INT,

    -- Columna IDA
    inicio_ida DATETIME,

    -- Columna VUELTA
    fin_ida DATETIME,
    inicio_vuelta DATETIME,
    fin_vuelta_estimado DATETIME,
    fin_vuelta_real DATETIME,

    -- Estado y retraso
    estado ENUM('en_curso', 'normal', 'retrasado'),
    tiempo_retraso_minutos INT,

    -- Control
    es_ultimo_viaje BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (driver_id) REFERENCES users(id),
    FOREIGN KEY (trip_ida_id) REFERENCES trips(id),
    FOREIGN KEY (trip_vuelta_id) REFERENCES trips(id)
);
```

### Componente React (DriverDashboard):

```jsx
// Botón en vista principal
<button onClick={() => setShowHorasModal(true)}>
    📅 Horas
</button>

// Modal con tabla
{showHorasModal && (
    <Modal title="Registro de Horas">
        <table>
            <thead>
                <tr>
                    <th>IDA</th>
                    <th>VUELTA</th>
                    <th>Estado</th>
                    <th>Retraso</th>
                </tr>
            </thead>
            <tbody>
                {timeRecords.map(record => (
                    <tr>
                        <td>
                            {record.inicio_ida && formatTime(record.inicio_ida)}
                        </td>
                        <td>
                            {record.fin_ida ? formatTime(record.fin_ida) :
                             record.fin_vuelta_estimado ? `Est: ${formatTime(record.fin_vuelta_estimado)}` : '-'}
                        </td>
                        <td>
                            <span className={record.estado === 'retrasado' ? 'text-red' : 'text-green'}>
                                {record.estado}
                            </span>
                        </td>
                        <td>
                            {record.tiempo_retraso_minutos ? `${record.tiempo_retraso_minutos} min` : '-'}
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    </Modal>
)}
```

---

## 🟡 FUNCIONALIDAD 3: Mejorar Panel Admin - Formulario de Usuarios

### Problema Actual:
El campo "Rol" aparece después de "Tipo de Usuario", causando confusión

### Solución:

#### Nuevo Orden del Formulario:

```
1. Rol (Pasajero / Chofer / Admin)
   ↓
2. [Si Rol = Pasajero] → Mostrar Tipo de Usuario
   - Regular (adulto) → 2.30 Bs
   - Estudiante Colegial → 1.00 Bs
   - Estudiante Universitario → 1.00 Bs
   - Menor de Edad → 1.00 Bs
   - Mayor de Edad → 1.00 Bs
   ↓
3. [Si Tipo = Estudiante] → Mostrar campos adicionales
   - Nombre de colegio/universidad
   - Foto de credencial
   - Fecha de vencimiento
   ↓
4. Datos generales (nombre, email, CI, etc.)
```

#### Lógica Condicional:

```jsx
const [rol, setRol] = useState('');
const [tipoUsuario, setTipoUsuario] = useState('');

// Al cambiar rol
const handleRolChange = (newRol) => {
    setRol(newRol);

    if (newRol !== 'pasajero') {
        // Limpiar campos de pasajero
        setTipoUsuario('');
        setSchoolName('');
        setCredentialPhoto(null);
    }
};

return (
    <>
        {/* 1. Primero el ROL */}
        <select value={rol} onChange={(e) => handleRolChange(e.target.value)}>
            <option value="">Seleccionar Rol</option>
            <option value="pasajero">Pasajero</option>
            <option value="chofer">Chofer</option>
            <option value="admin">Administrador</option>
        </select>

        {/* 2. Si es pasajero, mostrar TIPO */}
        {rol === 'pasajero' && (
            <select value={tipoUsuario} onChange={(e) => setTipoUsuario(e.target.value)}>
                <option value="">Seleccionar Tipo</option>
                <option value="regular">Regular (adulto) - 2.30 Bs</option>
                <option value="student_school">Estudiante Colegial - 1.00 Bs</option>
                <option value="student_university">Estudiante Universitario - 1.00 Bs</option>
                <option value="minor">Menor de Edad - 1.00 Bs</option>
                <option value="senior">Mayor de Edad - 1.00 Bs</option>
            </select>
        )}

        {/* 3. Si es estudiante, campos adicionales */}
        {(tipoUsuario === 'student_school' || tipoUsuario === 'student_university') && (
            <>
                <input
                    type="text"
                    placeholder="Nombre del colegio/universidad"
                    value={schoolName}
                    onChange={(e) => setSchoolName(e.target.value)}
                />
                <input
                    type="file"
                    accept="image/*"
                    onChange={(e) => setCredentialPhoto(e.target.files[0])}
                />
                <input
                    type="date"
                    value={credentialExpiry}
                    onChange={(e) => setCredentialExpiry(e.target.value)}
                />
            </>
        )}

        {/* 4. Resto de campos generales */}
        <input type="text" placeholder="Nombre completo" />
        <input type="email" placeholder="Email" />
        {/* ... más campos ... */}
    </>
);
```

---

## 📋 REORGANIZACIÓN DE DATOS Y FUNCIONES

### Tareas de Reorganización:

1. **Migraciones de Base de Datos**
   - [ ] Crear tabla `driver_time_records`
   - [ ] Agregar campo `tiempo_estimado_viaje` a tabla `rutas`
   - [ ] Agregar campo `es_ultimo_viaje` a tabla `trips`

2. **Controladores Backend**
   - [ ] Crear `TimeRecordController.php`
   - [ ] Métodos: `startTrip()`, `endTrip()`, `getRecords()`, `clearRecords()`

3. **Modelos**
   - [ ] Crear `TimeRecord.php`
   - [ ] Relaciones: `belongsTo(User)`, `belongsTo(Trip)`

4. **Frontend - DriverDashboard**
   - [ ] Agregar botón "Horas"
   - [ ] Crear modal `HorasModal.jsx`
   - [ ] Hook `useTimeRecords.js`
   - [ ] Integrar con inicio/fin de viaje

5. **Frontend - Admin Panel**
   - [ ] Reordenar formulario de usuarios
   - [ ] Lógica condicional para campos
   - [ ] Validaciones según tipo de usuario

---

## ⏱️ ESTIMACIÓN DE TIEMPO

| Tarea | Tiempo Estimado |
|-------|-----------------|
| Solucionar problema tarifas | 1 hora |
| Sistema de control de horas (backend) | 3-4 horas |
| Sistema de control de horas (frontend) | 3-4 horas |
| Mejorar formulario admin | 1-2 horas |
| Reorganización y testing | 2-3 horas |
| **TOTAL** | **10-14 horas** |

---

## 🎯 PRIORIDAD DE IMPLEMENTACIÓN

1. **URGENTE:** Solucionar problema de tarifas (1 hora)
2. **ALTA:** Sistema de control de horas backend (3-4 horas)
3. **ALTA:** Sistema de control de horas frontend (3-4 horas)
4. **MEDIA:** Mejorar formulario admin (1-2 horas)
5. **BAJA:** Reorganización general (2-3 horas)

---

**NOTA:** Todos estos cambios requieren:
- ✅ Migraciones de base de datos
- ✅ Endpoints nuevos en backend
- ✅ Componentes React nuevos
- ✅ Testing completo
- ✅ Documentación

**Estado:** 📝 Documentado - Pendiente de aprobación para iniciar

# 🚌 Interflow - Sistema de Cobro de Transporte Público

Sistema completo de gestión de cobros para transporte público con tecnología RFID, panel administrativo web y aplicaciones móviles.

## 📋 Características

- ✅ **Panel Administrativo Web** - Gestión completa de usuarios, rutas, buses y transacciones
- ✅ **Sistema de Tarjetas RFID** - Cobro automático mediante tarjetas NFC
- ✅ **Panel de Conductores** - Gestión de viajes y cobros en tiempo real
- ✅ **Panel de Pasajeros** - Consulta de saldo, historial y solicitudes de devolución
- ✅ **API RESTful** - Integración con dispositivos IoT (ESP8266/Arduino)
- ✅ **Autenticación Segura** - Laravel Sanctum para APIs
- ✅ **Sistema de Devoluciones** - Gestión de reembolsos y quejas

## 🛠️ Stack Tecnológico

### Backend
- **Framework:** Laravel 9
- **PHP:** 8.0.2+
- **Base de Datos:** MySQL
- **Autenticación:** Laravel Sanctum

### Frontend
- **Framework:** React 19
- **Routing:** React Router
- **UI:** Ionic React + Tailwind CSS
- **Build Tool:** Vite 7

### Hardware
- **Microcontrolador:** ESP8266 (NodeMCU)
- **Lector RFID:** MFRC522
- **Protocolo:** HTTPS/JSON

## 🚀 Deployment

El proyecto está configurado para desplegarse en Railway con Docker.

### Opción 1: Deploy en Railway (Recomendado)

Ver guía completa: [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)

**Resumen rápido:**
1. Conecta tu repositorio GitHub con Railway
2. Railway detectará automáticamente el `Dockerfile`
3. Configura las variables de entorno
4. Railway desplegará automáticamente

### Opción 2: Desarrollo Local

```bash
# 1. Clonar repositorio
git clone https://github.com/ByronHT/cobro-transporte.git
cd cobro-transporte

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos en .env
# Edita DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. Ejecutar migraciones
php artisan migrate

# 7. Iniciar servidor Laravel
php artisan serve

# 8. En otra terminal, iniciar Vite
npm run dev
```

## 📱 Arquitectura del Sistema

```
┌─────────────────────────────────────────────────┐
│              PANEL WEB ADMIN                    │
│         (Laravel Blade + Tailwind)              │
└─────────────────────────────────────────────────┘
                        │
                        ├─── API REST (Laravel)
                        │
        ┌───────────────┼───────────────┐
        │               │               │
┌───────▼──────┐ ┌─────▼──────┐ ┌─────▼──────┐
│   Panel      │ │   Panel    │ │  Arduino   │
│  Conductor   │ │  Pasajero  │ │ ESP8266 +  │
│  (React)     │ │  (React)   │ │   RFID     │
└──────────────┘ └────────────┘ └────────────┘
```

## 🗄️ Base de Datos

### Tablas Principales

- `users` - Usuarios del sistema (admin, conductor, pasajero)
- `cards` - Tarjetas RFID de pasajeros
- `buses` - Buses registrados en el sistema
- `rutas` - Rutas de transporte
- `trips` - Viajes realizados por conductores
- `transactions` - Transacciones de pago
- `refund_requests` - Solicitudes de devolución
- `payment_events` - Eventos de pago (auditoría)
- `bus_commands` - Comandos enviados a dispositivos IoT

## 🔒 Roles y Permisos

### Administrador
- Gestión completa del sistema
- CRUD de usuarios, buses, rutas
- Visualización de transacciones
- Aprobación de devoluciones

### Conductor
- Iniciar/finalizar viajes
- Ver transacciones del viaje actual
- Gestionar solicitudes de devolución

### Pasajero
- Ver saldo de tarjeta
- Historial de viajes
- Solicitar devoluciones
- Presentar quejas

## 📡 API Endpoints Principales

```
POST   /api/cliente/login           - Login de conductor/pasajero
GET    /api/cliente/profile         - Perfil del usuario
POST   /api/payment/process         - Procesar pago RFID
POST   /api/trips/start             - Iniciar viaje
POST   /api/trips/end               - Finalizar viaje
GET    /api/device/command/{bus}    - Polling de comandos (Arduino)
```

## 🔧 Configuración de Hardware

### Esquema de Conexión ESP8266 + MFRC522

```
ESP8266 (NodeMCU)    MFRC522
─────────────────    ───────
D8       (GPIO15)    SDA
D7       (GPIO13)    MOSI
D6       (GPIO12)    MISO
D5       (GPIO14)    SCK
D3       (GPIO0)     RST
3.3V                 3.3V
GND                  GND
```

### Código Arduino

El código para el ESP8266 está documentado en `MIGRACION_A_PRODUCCION.md` sección 7.

## 📝 Variables de Entorno

Ver `.env.example` para todas las configuraciones disponibles.

**Variables críticas:**
```env
APP_KEY=             # Generar con: php artisan key:generate
DB_HOST=             # Host de MySQL
DB_DATABASE=         # Nombre de base de datos
DB_USERNAME=         # Usuario MySQL
DB_PASSWORD=         # Contraseña MySQL
APP_URL=             # URL pública de la aplicación
```

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Ejecutar tests con coverage
vendor/bin/phpunit --coverage-html coverage
```

## 📄 Documentación Adicional

- [CLAUDE.md](CLAUDE.md) - Guía del proyecto para AI
- [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md) - Guía de despliegue en Railway
- [MIGRACION_A_PRODUCCION.md](MIGRACION_A_PRODUCCION.md) - Guía completa de migración

## 🤝 Contribuciones

Este es un proyecto académico. Para sugerencias o mejoras, contacta al autor.

## 📄 Licencia

Proyecto académico - Todos los derechos reservados

## 👤 Autor

**Brandon**
- GitHub: [@ByronHT](https://github.com/ByronHT)

---

**Versión:** 1.0.0
**Última actualización:** Noviembre 2025

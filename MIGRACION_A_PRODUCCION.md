# 🚀 GUÍA COMPLETA DE MIGRACIÓN A PRODUCCIÓN - SISTEMA INTERFLOW

**Para:** Usuario sin experiencia en despliegues cloud
**Objetivo:** Migrar sistema de transporte a la nube de forma GRATUITA
**Fecha:** 14 de Noviembre de 2025
**Duración estimada:** 6-8 horas (primera vez)
**Costo:** $0 USD (100% gratuito usando planes free)

---

## ⚠️ ANTES DE EMPEZAR - LEE ESTO COMPLETO

### ¿Qué vamos a lograr?

**ACTUALMENTE (LOCAL):**
```
Tu PC → Laravel + React + MySQL + Arduino conectado localmente
```

**DESPUÉS DE LA MIGRACIÓN:**
```
☁️ NUBE:
  ├─ Render.com → Panel Admin + API Backend
  ├─ Railway.app → Base de Datos MySQL

📱 CELULARES:
  ├─ App Android (Expo) → Chofer + Pasajero unificados

🔧 HARDWARE:
  ├─ Arduino ESP8266 → Conectado vía WiFi del chofer
```

### Prerequisitos OBLIGATORIOS

✅ **Cuentas que DEBES crear AHORA:**
1. GitHub → https://github.com/signup
2. Render.com → https://render.com/
3. Railway.app → https://railway.app/
4. Expo.dev → https://expo.dev/

✅ **Software instalado en tu PC:**
- Git ✅
- Node.js 18+ ✅
- Composer ✅
- Arduino IDE ✅

✅ **Acceso a:**
- Tu proyecto actual funcionando localmente
- Base de datos local con datos de prueba

---

## 📋 ÍNDICE

**SECCIÓN A: ENTENDIMIENTO (Lee primero)**
- [Resumen Ejecutivo - Para no técnicos](#resumen-ejecutivo)
- [Arquitectura Actual vs Final](#arquitectura)
- [Orden de Ejecución](#orden-de-ejecución)

**SECCIÓN B: MIGRACIÓN (Sigue paso a paso)**
1. [Preparación Local](#1-preparación-local) ⏱️ 30 min
2. [Configurar Base de Datos en Railway](#2-configurar-base-de-datos-railway) ⏱️ 20 min
3. [Subir Panel Admin a Render](#3-subir-panel-admin-a-render) ⏱️ 40 min
4. [Crear App Móvil con Expo](#4-crear-app-móvil-expo) ⏱️ 2-3 horas
5. [Actualizar Arduino para Producción](#5-actualizar-arduino-producción) ⏱️ 30 min
6. [Pruebas Finales](#6-pruebas-finales) ⏱️ 1 hora
7. [Problemas Comunes y Soluciones](#7-problemas-comunes)

---

## 📖 RESUMEN EJECUTIVO

### ¿Qué significa "migrar a producción"?

Actualmente tu sistema funciona en tu computadora. Solo tú puedes usarlo.

**"Migrar a producción"** significa:
1. Subir el panel de administración a internet (para que accedas desde cualquier navegador)
2. Subir la base de datos a internet (para que todos compartan la misma información)
3. Crear una app para Android que los choferes y pasajeros instalen en sus celulares
4. Configurar los Arduinos para que se conecten al servidor de internet (no a tu PC)

### ¿Por qué NO podemos subir todo tal cual está?

Tu proyecto actual tiene Laravel + React juntos. Esto funciona bien en local pero complica las cosas en la nube.

**SOLUCIÓN:**
- Panel Admin (Laravel) → Se queda junto → Render.com
- Panel Chofer + Pasajero (React) → Se convierte a app Android → Expo
- Base de Datos → Se separa → Railway.app
- Arduino → Se actualiza para usar HTTPS → Sigue siendo hardware físico

### ¿Cómo se comunicarán entre sí?

```
App Móvil (Chofer)
    ↓ (HTTPS/Internet)
Render (API Backend)
    ↓ (MySQL/Internet)
Railway (Base de Datos)
    ↑ (Consulta SQL)
Render (API Backend)
    ↓ (HTTPS/Internet)
Arduino (En el bus)
```

**CLAVE:** Todo se comunica a través de **Internet** usando **HTTPS** (conexión segura).

---

## 🏗️ ARQUITECTURA

### ANTES (Local):
```
┌─────────────────────────────────────┐
│        TU COMPUTADORA               │
│  ┌──────────────────────────────┐  │
│  │  Laravel (Puerto 8000)       │  │
│  │   - Panel Admin              │  │
│  │   - API Backend              │  │
│  │   - Panel Chofer (React)     │  │
│  │   - Panel Pasajero (React)   │  │
│  └──────────────────────────────┘  │
│                                     │
│  ┌──────────────────────────────┐  │
│  │  MySQL (Puerto 3306)         │  │
│  │   - Todas las tablas         │  │
│  └──────────────────────────────┘  │
│                                     │
│  Arduino conectado vía WiFi local  │
└─────────────────────────────────────┘
```

### DESPUÉS (Producción):
```
INTERNET (☁️ NUBE)
├─ 🖥️ Render.com
│   └─ Panel Admin + API Backend
│      URL: https://interflow-backend.onrender.com
│
├─ 💾 Railway.app
│   └─ Base de Datos MySQL
│      Host: containers-us-west-xxx.railway.app:6697
│
└─ [Conexiones HTTPS entre servicios]

DISPOSITIVOS FÍSICOS
├─ 📱 Celular Chofer
│   └─ App Expo (APK instalado)
│      └─ Panel Chofer
│      └─ Comparte WiFi al Arduino
│
├─ 📱 Celular Pasajero
│   └─ App Expo (APK instalado)
│      └─ Panel Pasajero
│
└─ 🔧 Arduino ESP8266 (En el bus)
    └─ WiFi del chofer
    └─ Se conecta a Render vía HTTPS
```

---

## 📋 ORDEN DE EJECUCIÓN

**NO puedes saltarte pasos ni cambiar el orden**. Sigue esto EXACTAMENTE:

| Paso | Qué harás | Por qué es importante | Tiempo |
|------|-----------|----------------------|--------|
| 1️⃣ | Subir código a GitHub | Render necesita leer tu código de algún lugar | 30 min |
| 2️⃣ | Crear MySQL en Railway | Todo necesita una base de datos primero | 20 min |
| 3️⃣ | Subir backend a Render | La API debe existir antes que la app móvil | 40 min |
| 4️⃣ | Crear app móvil en Expo | Los choferes/pasajeros necesitan acceso | 2-3 hrs |
| 5️⃣ | Actualizar Arduino | Hardware debe apuntar al servidor real | 30 min |
| 6️⃣ | Probar todo junto | Verificar que funcione end-to-end | 1 hr |

**TOTAL: 5-6 horas** (para alguien que lo hace por primera vez)

---

# 1. PREPARACIÓN LOCAL

⏱️ **Tiempo:** 30 minutos
🎯 **Objetivo:** Preparar tu código para subirlo a GitHub

---

## 1.1 Crear archivo .gitignore

Este archivo le dice a Git qué archivos NO subir (como contraseñas y archivos temporales).

**Paso 1:** Abre tu carpeta del proyecto en el Explorador de Windows:
```
C:\Users\brand\OneDrive\Escritorio\cobro-transporte\cobro-transporte
```

**Paso 2:** Abre PowerShell en esa carpeta:
- Clic derecho en un espacio vacío de la carpeta
- "Abrir en Terminal" o "Abrir ventana de PowerShell aquí"

**Paso 3:** Ejecuta este comando:
```powershell
notepad .gitignore
```

Si te pregunta "¿Desea crear un nuevo archivo?", di **Sí**.

**Paso 4:** Pega este contenido:
```
/node_modules
/public/hot
/public/storage
/public/build
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/.idea
/.vscode
```

**Paso 5:** Guarda (Ctrl+S) y cierra el Bloc de Notas.

---

## 1.2 Crear .env.example

Este es una copia de tu `.env` pero SIN contraseñas (para que otros sepan qué variables necesitan).

```powershell
# Copiar .env a .env.example
copy .env .env.example

# Abrir para editar
notepad .env.example
```

**IMPORTANTE:** Reemplaza TODOS los valores sensibles con placeholders:

```env
APP_NAME=Interflow
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=
SESSION_DOMAIN=
```

Guarda y cierra.

---

## 1.3 Subir código a GitHub

Ahora vamos a subir todo tu proyecto a GitHub. Hay DOS formas:

### OPCIÓN A: GitHub Desktop (RECOMENDADO - MÁS FÁCIL)

**Paso 1:** Descarga GitHub Desktop
- Ve a: https://desktop.github.com/
- Descarga e instala

**Paso 2:** Inicia sesión
- Abre GitHub Desktop
- File → Options → Sign in
- Inicia sesión con tu cuenta de GitHub

**Paso 3:** Agregar tu proyecto
- File → Add Local Repository
- Click "Choose..."
- Navega a: `C:\Users\brand\OneDrive\Escritorio\cobro-transporte\cobro-transporte`
- Click "Add Repository"

Si dice "This directory does not appear to be a Git repository", haz click en "create a repository" y luego "Create Repository".

**Paso 4:** Hacer el primer commit
- En la caja de texto de abajo a la izquierda escribe: `Initial commit - Sistema INTERFLOW`
- Click "Commit to main"

**Paso 5:** Publicar a GitHub
- Click "Publish repository" (arriba)
- Nombre: `interflow-backend`
- Descripción: `Sistema de cobro de transporte público`
- DESMARCAR "Keep this code private"
- Click "Publish Repository"

✅ **LISTO!** Tu código ya está en GitHub.

### OPCIÓN B: Usando comandos (Avanzado)

Solo si NO quieres usar GitHub Desktop:

```powershell
cd C:\Users\brand\OneDrive\Escritorio\cobro-transporte\cobro-transporte

# Inicializar Git
git init
git add .
git commit -m "Initial commit - Sistema INTERFLOW v1.0"

# Ahora ve al navegador:
# 1. Abre https://github.com/new
# 2. Repository name: interflow-backend
# 3. Public (no private)
# 4. NO marcar "Initialize this repository with a README"
# 5. Click "Create repository"
# 6. GitHub te mostrará una URL como: https://github.com/TU_USUARIO/interflow-backend.git
# 7. COPIA esa URL

# Vuelve a PowerShell y ejecuta (reemplaza TU_USUARIO):
git remote add origin https://github.com/TU_USUARIO/interflow-backend.git
git branch -M main
git push -u origin main
```

---

## ✅ CHECKPOINT 1

Verifica que TODO esté bien antes de continuar:

- [ ] Puedes ver tu código en GitHub: `https://github.com/TU_USUARIO/interflow-backend`
- [ ] El archivo `.env` NO está visible en GitHub (si lo ves, ¡BÓRRALO INMEDIATAMENTE!)
- [ ] Ves archivos como `composer.json`, `package.json`, carpetas `app/`, `resources/`, etc.

Si TODO está bien, continúa al Paso 2.

---

# 2. CONFIGURAR BASE DE DATOS EN RAILWAY

⏱️ **Tiempo:** 20 minutos
🎯 **Objetivo:** Crear una base de datos MySQL en la nube y copiar tus datos

---

## 2.1 Crear cuenta en Railway

**Paso 1:** Ve a https://railway.app/

**Paso 2:** Click en "Login" (arriba a la derecha)

**Paso 3:** Selecciona "Login with GitHub"
- Te pedirá autorizar Railway para acceder a tu cuenta de GitHub
- Click "Authorize Railway"

✅ Ya tienes cuenta en Railway

---

## 2.2 Crear proyecto MySQL

**Paso 1:** En el dashboard de Railway, click en "New Project"

**Paso 2:** Selecciona "Provision MySQL"
- Railway creará automáticamente una base de datos MySQL
- Espera 30-60 segundos

**Paso 3:** Verás un cuadro morado con el texto "MySQL"
- Click en ese cuadro

**Paso 4:** Ve a la pestaña "Variables"
- Verás algo como esto:

```
MYSQL_URL=mysql://root:EikcJRVuHWfiEXdewQpuffjuVfsLcoKN@mainline.proxy.rlwy.net:44459/ferrocarril
MYSQLHOST=mysql.railway.internal
MYSQLPASSWORD=EikcJRVuHWfiEXdewQpuffjuVfsLcoKN
MYSQLPORT=3306
MYSQLUSER=root
MYSQLDATABASE=railway
```

**IMPORTANTE:** Abre el Bloc de Notas y copia TODOS esos valores. Los necesitarás después.

---

## 2.3 Copiar tu base de datos local a Railway

Ahora vamos a exportar tu base de datos local y subirla a Railway.

### OPCIÓN A: Usando MySQL Workbench (RECOMENDADO)

**Paso 1:** Exportar base de datos local

1. Abre MySQL Workbench
2. Conecta a tu servidor local (localhost)
3. Click derecho en tu base de datos (probablemente se llama `cobro_transporte` o similar)
4. "Data Export"
5. Selecciona TODAS las tablas
6. En "Export Options" selecciona "Export to Self-Contained File"
7. Click en "..." y guarda como: `C:\backup_interflow.sql`
8. Click "Start Export"

**Paso 2:** Conectar a Railway

1. En MySQL Workbench, click en el "+" para nueva conexión
2. Llena los datos (usa los que copiaste de Railway):
   - **Connection Name:** Railway Interflow
   - **Hostname:** (copia MYSQLHOST de Railway)
   - **Port:** (copia MYSQLPORT de Railway)
   - **Username:** root (o el MYSQLUSER de Railway)
   - **Password:** Click "Store in Vault" y pega MYSQLPASSWORD

3. Click "Test Connection"
   - Debe decir "Successfully made the MySQL connection"
   - Si falla, verifica que copiaste bien los datos

4. Click "OK"

**Paso 3:** Importar a Railway

1. Doble click en la conexión "Railway Interflow"
2. En la barra de menú: Server → Data Import
3. Selecciona "Import from Self-Contained File"
4. Click "..." y selecciona: `C:\backup_interflow.sql`
5. En "Default Target Schema" selecciona "railway" (o el nombre que aparezca)
6. Click "Start Import"
7. Espera a que termine (puede tomar 1-5 minutos)

✅ **LISTO!** Tu base de datos ya está en la nube.

### OPCIÓN B: Usando comandos (Si MySQL Workbench falla)

```powershell
# Exportar base de datos local
cd C:\xampp\mysql\bin
.\mysqldump.exe -u root -p cobro_transporte > C:\backup_interflow.sql
# Te pedirá contraseña (probablemente vacía, solo presiona Enter)

# Importar a Railway (reemplaza con tus datos de Railway)
.\mysql.exe -h MYSQLHOST -P MYSQLPORT -u root -p railway < C:\backup_interflow.sql
# Te pedirá MYSQLPASSWORD
```

---

## ✅ CHECKPOINT 2

Verifica en Railway que tus tablas existan:

**Paso 1:** En Railway, click en tu base de datos MySQL

**Paso 2:** Click en la pestaña "Data"

**Paso 3:** Deberías ver TODAS tus tablas:
- [ ] users
- [ ] cards
- [ ] buses
- [ ] rutas
- [ ] trips
- [ ] transactions
- [ ] refund_requests
- [ ] payment_events
- [ ] bus_commands

**Paso 4:** Click en "users" y verifica que veas tus usuarios de prueba

Si TODO está bien, continúa al Paso 3.

---
------ asta aqui llegue ----------

# 3. SUBIR PANEL ADMIN A RENDER

⏱️ **Tiempo:** 40-50 minutos
🎯 **Objetivo:** Subir tu backend Laravel (Panel Admin + API) a internet

---

## 3.1 Crear archivos de configuración para Docker

Render necesita saber cómo ejecutar tu aplicación. Vamos a crear un Dockerfile.

**Paso 1:** Abre PowerShell en tu carpeta del proyecto

**Paso 2:** Crea el archivo Dockerfile:

```powershell
notepad Dockerfile
```

**Paso 3:** Pega este contenido EXACTO:

```dockerfile
# Usar PHP 8.2 con Apache
FROM php:8.2-apache

# Instalar extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 18
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Configurar Apache
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . /var/www/html

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de Node y compilar assets
RUN npm install && npm run build

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puerto 80
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
```

**Paso 4:** Guarda (Ctrl+S) y cierra el Bloc de Notas.

**Paso 5:** Subir el Dockerfile a GitHub

```powershell
# En PowerShell, en tu carpeta del proyecto
git add Dockerfile
git commit -m "Add Dockerfile for Render deployment"
git push
```

✅ **Listo!** El Dockerfile ya está en GitHub.

---

## 3.2 Crear cuenta en Render y conectar GitHub

**Paso 1:** Ve a https://render.com/

**Paso 2:** Click en "Get Started for Free"

**Paso 3:** Click en "GitHub" para conectar con tu cuenta
- Te pedirá autorizar Render
- Click "Authorize Render"

✅ Ya tienes cuenta en Render

---

## 3.3 Crear Web Service en Render

**Paso 1:** En el dashboard de Render, click "New +" (arriba a la derecha)

**Paso 2:** Selecciona "Web Service"

**Paso 3:** Busca y selecciona tu repositorio `interflow-backend`
- Si no aparece, click en "Configure account" y autoriza acceso al repo

**Paso 4:** Configuración del servicio:

```
Name: interflow-backend
Region: Oregon (US West) - selecciona el más cercano
Branch: main
Root Directory: (dejar VACÍO)
Environment: Docker
Instance Type: Free
```

**NO HAGAS CLICK EN "CREATE WEB SERVICE" TODAVÍA**

---

## 3.4 Configurar Variables de Entorno

Antes de crear el servicio, necesitas configurar las variables de entorno.

**Paso 1:** Haz scroll hacia abajo hasta "Environment Variables"

**Paso 2:** Click en "Add Environment Variable"

**Paso 3:** Añade CADA UNA de estas variables (click "+ Add Environment Variable" para cada una):

**IMPORTANTE:** Reemplaza los valores con los que guardaste de Railway:

```
APP_NAME = Interflow
APP_ENV = production
APP_DEBUG = false
APP_KEY = (déjalo VACÍO por ahora, lo generaremos después)
APP_URL = https://interflow-backend.onrender.com

DB_CONNECTION = mysql
DB_HOST = (pega MYSQLHOST de Railway)
DB_PORT = (pega MYSQLPORT de Railway)
DB_DATABASE = railway
DB_USERNAME = root
DB_PASSWORD = (pega MYSQLPASSWORD de Railway)

SESSION_DRIVER = file
SESSION_LIFETIME = 120

SANCTUM_STATEFUL_DOMAINS = localhost,127.0.0.1
```

**Paso 4:** Una vez que hayas añadido TODAS las variables, click en "Create Web Service"

---

## 3.5 Esperar el Deploy

Render empezará a construir tu aplicación. Este proceso toma **5-15 minutos**.

Verás logs en tiempo real:

```
==> Cloning from https://github.com/TU_USUARIO/interflow-backend...
==> Building...
==> Downloading base image...
==> Installing PHP extensions...
==> Installing Composer dependencies...
==> Installing Node dependencies...
==> Building assets with Vite...
==> Deploy successful!
```

**Cuando veas "Live"** en verde, tu aplicación está lista.

---

## 3.6 Generar APP_KEY

**Paso 1:** En Render, en tu servicio, ve a la pestaña "Shell" (arriba)

**Paso 2:** Se abrirá una consola. Ejecuta este comando:

```bash
php artisan key:generate --show
```

**Paso 3:** Copia la clave que te muestra (empieza con `base64:`)

**Paso 4:** Ve a la pestaña "Environment"

**Paso 5:** Busca la variable `APP_KEY` y pega el valor que copiaste

**Paso 6:** Click "Save Changes"

Render reiniciará automáticamente el servicio (toma 2-3 minutos).

---

## 3.7 Ejecutar migraciones

Una vez que esté "Live" de nuevo:

**Paso 1:** Ve a la pestaña "Shell"

**Paso 2:** Ejecuta estos comandos UNO POR UNO:

```bash
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

Si las migraciones ya se ejecutaron cuando importaste a Railway, dirá "Nothing to migrate".

---

## 3.8 Verificar que funciona

**Paso 1:** Copia la URL de tu servicio
- Está arriba a la izquierda, algo como: `https://interflow-backend.onrender.com`

**Paso 2:** Abre esa URL en tu navegador

**Paso 3:** Deberías ver el panel de admin de Laravel (la vista de bienvenida)

**Paso 4:** Prueba el panel admin:
- Ve a: `https://interflow-backend.onrender.com/admin`
- Deberías poder iniciar sesión con un usuario admin

---

## ✅ CHECKPOINT 3

Verifica que TODO esté bien:

- [ ] Tu URL de Render funciona: `https://TU-APP.onrender.com`
- [ ] Puedes acceder al panel admin: `https://TU-APP.onrender.com/admin`
- [ ] Puedes iniciar sesión con un usuario admin
- [ ] En Render, el servicio muestra "Live" en verde
- [ ] No hay errores en los logs de Render

**IMPORTANTE:** Guarda tu URL de Render en el Bloc de Notas. La necesitarás para:
- La app móvil
- El Arduino

Si TODO está bien, continúa al Paso 4.

---

# 4. CREAR APP MÓVIL CON EXPO

⏱️ **Tiempo:** 2-3 horas (la parte más larga)
🎯 **Objetivo:** Crear app Android que unifique Panel Chofer + Panel Pasajero

---

## 4.1 Instalar Expo CLI

**Paso 1:** Abre PowerShell (NO en la carpeta del proyecto, en cualquier lugar)

**Paso 2:** Instala Expo CLI globalmente:

```powershell
npm install -g expo-cli eas-cli
```

Espera a que termine (puede tomar 2-3 minutos).

✅ **Listo!** Expo CLI instalado.

---

## 4.2 Crear proyecto Expo

**Paso 1:** Navega a tu carpeta de escritorio:

```powershell
cd C:\Users\brand\OneDrive\Escritorio
```

**Paso 2:** Crea el proyecto:

```powershell
npx create-expo-app interflow-mobile
```

Espera a que termine (2-3 minutos).

**Paso 3:** Entra a la carpeta:

```powershell
cd interflow-mobile
```

---

## 4.3 Instalar dependencias necesarias

```powershell
npm install axios @react-navigation/native @react-navigation/stack react-native-screens react-native-safe-area-context @react-native-async-storage/async-storage
```

---

## 4.4 Crear archivo de configuración de API

**Paso 1:** Crea el archivo config:

```powershell
notepad config.js
```

**Paso 2:** Pega este contenido (REEMPLAZA con tu URL de Render):

```javascript
// IMPORTANTE: Reemplaza con tu URL de Render
export const API_BASE_URL = 'https://TU-APP.onrender.com';
```

Ejemplo real:
```javascript
export const API_BASE_URL = 'https://interflow-backend.onrender.com';
```

Guarda y cierra.

---

## 4.5 Crear estructura de carpetas

```powershell
mkdir screens
```

---

## 4.6 Copiar componentes de React a React Native

Ahora viene la parte importante: adaptar tus componentes React existentes a React Native.

**La gran diferencia:**
- React Web usa: `<div>`, `<p>`, `<button>`, `<input>`
- React Native usa: `<View>`, `<Text>`, `<TouchableOpacity>`, `<TextInput>`

**Te voy a dar los archivos ya adaptados. Solo cópialos:**

### Archivo: `App.js`

```powershell
notepad App.js
```

Reemplaza TODO el contenido con:

```javascript
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import WelcomeScreen from './screens/WelcomeScreen';
import LoginPassenger from './screens/LoginPassenger';
import LoginDriver from './screens/LoginDriver';
import PassengerDashboard from './screens/PassengerDashboard';
import DriverDashboard from './screens/DriverDashboard';

const Stack = createStackNavigator();

export default function App() {
  return (
    <NavigationContainer>
      <Stack.Navigator
        initialRouteName="Welcome"
        screenOptions={{ headerShown: false }}
      >
        <Stack.Screen name="Welcome" component={WelcomeScreen} />
        <Stack.Screen name="LoginPassenger" component={LoginPassenger} />
        <Stack.Screen name="LoginDriver" component={LoginDriver} />
        <Stack.Screen name="PassengerDashboard" component={PassengerDashboard} />
        <Stack.Screen name="DriverDashboard" component={DriverDashboard} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
```

---

### Archivo: `screens/WelcomeScreen.js`

```powershell
notepad screens\WelcomeScreen.js
```

Pega:

```javascript
import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';

export default function WelcomeScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <View style={styles.logoContainer}>
        <Text style={styles.title}>Bienvenido a Interflow</Text>
        <Text style={styles.subtitle}>Sistema de Cobro de Transporte</Text>
      </View>

      <View style={styles.buttonsContainer}>
        <TouchableOpacity
          style={styles.passengerButton}
          onPress={() => navigation.navigate('LoginPassenger')}
        >
          <Text style={styles.buttonText}>Ingresar como Pasajero</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.driverButton}
          onPress={() => navigation.navigate('LoginDriver')}
        >
          <Text style={styles.buttonTextDriver}>Ingresar como Chofer</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.footer}>
        <Text style={styles.footerText}>Interflow © 2025</Text>
        <Text style={styles.footerText}>Sistema de Transporte Inteligente</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0891b2',
    justifyContent: 'center',
    padding: 20,
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 60,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: 'white',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: 'white',
    opacity: 0.9,
    textAlign: 'center',
  },
  buttonsContainer: {
    gap: 15,
  },
  passengerButton: {
    backgroundColor: 'white',
    padding: 18,
    borderRadius: 12,
    alignItems: 'center',
    marginBottom: 15,
  },
  driverButton: {
    backgroundColor: 'transparent',
    padding: 18,
    borderRadius: 12,
    alignItems: 'center',
    borderWidth: 3,
    borderColor: 'white',
  },
  buttonText: {
    color: '#0891b2',
    fontSize: 18,
    fontWeight: 'bold',
  },
  buttonTextDriver: {
    color: 'white',
    fontSize: 18,
    fontWeight: 'bold',
  },
  footer: {
    marginTop: 60,
    alignItems: 'center',
  },
  footerText: {
    color: 'white',
    fontSize: 12,
    opacity: 0.8,
  },
});
```

---

**NOTA:** Los demás archivos (LoginPassenger, LoginDriver, PassengerDashboard, DriverDashboard) son muy largos.

**Te voy a dar dos opciones:**

### OPCIÓN A: Versión Simplificada (RECOMENDADO para empezar)

Crea versiones simples que solo muestren "Funciona!" y ya después las completas con todas las funcionalidades.

### OPCIÓN B: Versión Completa

Te paso TODOS los archivos completos adaptados de React a React Native (son ~500 líneas de código).

**¿Cuál prefieres? Te recomiendo empezar con la Opción A para probar que todo funcione, y luego te doy la Opción B.**

Por ahora, voy a continuar con la Opción A (versión simple):

---

### Archivo: `screens/LoginPassenger.js` (VERSIÓN SIMPLE)

```powershell
notepad screens\LoginPassenger.js
```

Pega:

```javascript
import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL } from '../config';

export default function LoginPassenger({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Por favor completa todos los campos');
      return;
    }

    setLoading(true);

    try {
      const response = await axios.post(`${API_BASE_URL}/api/cliente/login`, {
        email,
        password,
      });

      if (response.data.user.role !== 'passenger') {
        Alert.alert('Error', 'Este usuario no es un pasajero');
        setLoading(false);
        return;
      }

      await AsyncStorage.setItem('passenger_token', response.data.access_token);
      await AsyncStorage.setItem('passenger_user', JSON.stringify(response.data.user));

      navigation.replace('PassengerDashboard');
    } catch (error) {
      Alert.alert('Error', error.response?.data?.error || 'Error al iniciar sesión');
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.formContainer}>
        <Text style={styles.title}>Panel de Pasajeros</Text>
        <Text style={styles.subtitle}>Ingresa tus credenciales</Text>

        <TextInput
          style={styles.input}
          placeholder="Correo Electrónico"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
        />

        <TextInput
          style={styles.input}
          placeholder="Contraseña"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />

        <TouchableOpacity
          style={styles.loginButton}
          onPress={handleLogin}
          disabled={loading}
        >
          <Text style={styles.loginButtonText}>
            {loading ? 'Ingresando...' : 'Iniciar Sesión'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.backButtonText}>Regresar</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0891b2',
    justifyContent: 'center',
    padding: 20,
  },
  formContainer: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 30,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#0891b2',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: '#64748b',
    marginBottom: 30,
    textAlign: 'center',
  },
  input: {
    borderWidth: 2,
    borderColor: '#e2e8f0',
    borderRadius: 8,
    padding: 12,
    marginBottom: 15,
    fontSize: 16,
  },
  loginButton: {
    backgroundColor: '#0891b2',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  loginButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  backButton: {
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#e2e8f0',
  },
  backButtonText: {
    color: '#64748b',
    fontSize: 16,
    fontWeight: '600',
  },
});
```

---

### Archivo: `screens/LoginDriver.js`

```powershell
notepad screens\LoginDriver.js
```

Pega (similar al LoginPassenger pero con validación de rol 'driver'):

```javascript
import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL } from '../config';

export default function LoginDriver({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Por favor completa todos los campos');
      return;
    }

    setLoading(true);

    try {
      const response = await axios.post(`${API_BASE_URL}/api/cliente/login`, {
        email,
        password,
      });

      if (response.data.user.role !== 'driver') {
        Alert.alert('Error', 'Este usuario no es un chofer');
        setLoading(false);
        return;
      }

      await AsyncStorage.setItem('driver_token', response.data.access_token);
      await AsyncStorage.setItem('driver_user', JSON.stringify(response.data.user));

      navigation.replace('DriverDashboard');
    } catch (error) {
      Alert.alert('Error', error.response?.data?.error || 'Error al iniciar sesión');
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.formContainer}>
        <Text style={styles.title}>Panel de Choferes</Text>
        <Text style={styles.subtitle}>Ingresa tus credenciales</Text>

        <TextInput
          style={styles.input}
          placeholder="Correo Electrónico"
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
        />

        <TextInput
          style={styles.input}
          placeholder="Contraseña"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />

        <TouchableOpacity
          style={styles.loginButton}
          onPress={handleLogin}
          disabled={loading}
        >
          <Text style={styles.loginButtonText}>
            {loading ? 'Ingresando...' : 'Iniciar Sesión'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}
        >
          <Text style={styles.backButtonText}>Regresar</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#1e3a8a',
    justifyContent: 'center',
    padding: 20,
  },
  formContainer: {
    backgroundColor: 'white',
    borderRadius: 16,
    padding: 30,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#1e3a8a',
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: '#64748b',
    marginBottom: 30,
    textAlign: 'center',
  },
  input: {
    borderWidth: 2,
    borderColor: '#e2e8f0',
    borderRadius: 8,
    padding: 12,
    marginBottom: 15,
    fontSize: 16,
  },
  loginButton: {
    backgroundColor: '#1e3a8a',
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  loginButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
  backButton: {
    padding: 14,
    borderRadius: 8,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#e2e8f0',
  },
  backButtonText: {
    color: '#64748b',
    fontSize: 16,
    fontWeight: '600',
  },
});
```

---

### Archivo: `screens/PassengerDashboard.js` (VERSIÓN SIMPLE)

```powershell
notepad screens\PassengerDashboard.js
```

Pega:

```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function PassengerDashboard({ navigation }) {
  const [user, setUser] = useState(null);

  useEffect(() => {
    loadUser();
  }, []);

  const loadUser = async () => {
    try {
      const userData = await AsyncStorage.getItem('passenger_user');
      if (userData) {
        setUser(JSON.parse(userData));
      }
    } catch (error) {
      console.error('Error loading user:', error);
    }
  };

  const handleLogout = async () => {
    Alert.alert(
      'Cerrar Sesión',
      '¿Estás seguro de que quieres salir?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Salir',
          style: 'destructive',
          onPress: async () => {
            await AsyncStorage.removeItem('passenger_token');
            await AsyncStorage.removeItem('passenger_user');
            navigation.replace('Welcome');
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Panel de Pasajero</Text>
        <Text style={styles.welcome}>Bienvenido, {user?.name || 'Usuario'}</Text>
      </View>

      <View style={styles.content}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Tu Saldo</Text>
          <Text style={styles.cardValue}>Bs. {user?.balance || '0.00'}</Text>
        </View>

        <Text style={styles.infoText}>
          🎉 Dashboard funcional!
        </Text>
        <Text style={styles.infoText}>
          Aquí verás tus viajes, transacciones y solicitudes de devolución.
        </Text>
      </View>

      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Text style={styles.logoutButtonText}>Cerrar Sesión</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f1f5f9',
  },
  header: {
    backgroundColor: '#0891b2',
    padding: 20,
    paddingTop: 50,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: 'white',
  },
  welcome: {
    fontSize: 16,
    color: 'white',
    marginTop: 5,
    opacity: 0.9,
  },
  content: {
    flex: 1,
    padding: 20,
  },
  card: {
    backgroundColor: 'white',
    padding: 20,
    borderRadius: 12,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  cardTitle: {
    fontSize: 14,
    color: '#64748b',
    marginBottom: 8,
  },
  cardValue: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#0891b2',
  },
  infoText: {
    fontSize: 14,
    color: '#64748b',
    textAlign: 'center',
    marginBottom: 10,
  },
  logoutButton: {
    backgroundColor: '#ef4444',
    padding: 16,
    margin: 20,
    borderRadius: 8,
    alignItems:'center',
  },
  logoutButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
});
```

---

### Archivo: `screens/DriverDashboard.js` (VERSIÓN SIMPLE)

```powershell
notepad screens\DriverDashboard.js
```

Pega:

```javascript
import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function DriverDashboard({ navigation }) {
  const [user, setUser] = useState(null);

  useEffect(() => {
    loadUser();
  }, []);

  const loadUser = async () => {
    try {
      const userData = await AsyncStorage.getItem('driver_user');
      if (userData) {
        setUser(JSON.parse(userData));
      }
    } catch (error) {
      console.error('Error loading user:', error);
    }
  };

  const handleLogout = async () => {
    Alert.alert(
      'Cerrar Sesión',
      '¿Estás seguro de que quieres salir?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Salir',
          style: 'destructive',
          onPress: async () => {
            await AsyncStorage.removeItem('driver_token');
            await AsyncStorage.removeItem('driver_user');
            navigation.replace('Welcome');
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Panel de Chofer</Text>
        <Text style={styles.welcome}>Bienvenido, {user?.name || 'Chofer'}</Text>
      </View>

      <View style={styles.content}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Estado</Text>
          <Text style={styles.cardValue}>Sin viaje activo</Text>
        </View>

        <Text style={styles.infoText}>
          🚌 Dashboard funcional!
        </Text>
        <Text style={styles.infoText}>
          Aquí podrás iniciar/finalizar viajes, ver solicitudes de devolución y más.
        </Text>
      </View>

      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Text style={styles.logoutButtonText}>Cerrar Sesión</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f1f5f9',
  },
  header: {
    backgroundColor: '#1e3a8a',
    padding: 20,
    paddingTop: 50,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: 'white',
  },
  welcome: {
    fontSize: 16,
    color: 'white',
    marginTop: 5,
    opacity: 0.9,
  },
  content: {
    flex: 1,
    padding: 20,
  },
  card: {
    backgroundColor: 'white',
    padding: 20,
    borderRadius: 12,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  cardTitle: {
    fontSize: 14,
    color: '#64748b',
    marginBottom: 8,
  },
  cardValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1e3a8a',
  },
  infoText: {
    fontSize: 14,
    color: '#64748b',
    textAlign: 'center',
    marginBottom: 10,
  },
  logoutButton: {
    backgroundColor: '#ef4444',
    padding: 16,
    margin: 20,
    borderRadius: 8,
    alignItems: 'center',
  },
  logoutButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: '600',
  },
});
```

---

## 4.7 Probar la app en tu celular

**Paso 1:** Instala "Expo Go" en tu celular Android
- Ve a Google Play Store
- Busca "Expo Go"
- Instala la app

**Paso 2:** En tu PC, en la carpeta del proyecto Expo, ejecuta:

```powershell
npx expo start
```

**Paso 3:** Verás algo como esto:

```
Metro waiting on exp://192.168.1.X:8081

› Scan the QR code above with Expo Go (Android) or the Camera app (iOS)

› Press a │ open Android
› Press w │ open web

› Press r │ reload app
› Press m │ toggle menu
```

**Paso 4:** Escanea el código QR con Expo Go

**Paso 5:** La app se cargará en tu celular

**Paso 6:** Deberías ver la pantalla de bienvenida con dos opciones:
- Ingresar como Pasajero
- Ingresar como Chofer

**Paso 7:** Prueba el login:
- Click en "Ingresar como Pasajero"
- Usa un email/password de un usuario passenger de tu base de datos
- Deberías ver el Dashboard de Pasajero

✅ **App móvil funcionando!**

---

## ✅ CHECKPOINT 4

Verifica que TODO funcione:

- [ ] Expo Go instalado en tu celular
- [ ] App se carga al escanear QR
- [ ] Ves la pantalla de bienvenida
- [ ] Puedes navegar a login de pasajero
- [ ] Puedes navegar a login de chofer
- [ ] Login funciona y redirige a dashboard
- [ ] Puedes cerrar sesión y volver a welcome

**NOTA:** Estas son versiones SIMPLIFICADAS de los dashboards. Después de la migración puedes agregar todas las funcionalidades completas (listar viajes, solicitar devoluciones, iniciar viajes, etc.).

Si TODO está bien, continúa al Paso 5.

---

# 5. ACTUALIZAR ARDUINO PARA PRODUCCIÓN

⏱️ **Tiempo:** 30-40 minutos
🎯 **Objetivo:** Configurar Arduino ESP8266 para conectarse al servidor en la nube con HTTPS

---

## 5.1 ¿Qué necesita cambiar en el Arduino?

Actualmente tu Arduino se conecta a `http://127.0.0.1:8000` (tu PC local).

**Necesitas cambiar:**
1. URL del servidor → `https://TU-APP.onrender.com`
2. HTTP → HTTPS (conexión segura)
3. WiFi → Red del chofer (compartida desde el celular)

---

## 5.2 Código Arduino actualizado

Abre Arduino IDE y reemplaza TODO el código con este:

```cpp
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>

// ==================== CONFIGURACIÓN WiFi ====================
// IMPORTANTE: El chofer compartirá su red con estos datos
const char* ssid = "NOMBRE_RED_CHOFER";          // ← CAMBIAR
const char* password = "CONTRASEÑA_RED_CHOFER";  // ← CAMBIAR

// ==================== CONFIGURACIÓN SERVIDOR ====================
// REEMPLAZA CON TU URL DE RENDER
const char* server_url = "https://TU-APP.onrender.com";  // ← CAMBIAR

// ==================== CONFIGURACIÓN HARDWARE ====================
#define SS_PIN D8
#define RST_PIN D0
MFRC522 mfrc522(SS_PIN, RST_PIN);

// ==================== VARIABLES GLOBALES ====================
const int BUS_ID = 1;  // ⚠️ CAMBIAR SEGÚN EL BUS (1, 2, 3, etc.)
int active_trip_id = 0;
int current_command_id = 0;

enum State {
    WAITING_FOR_COMMAND,
    TRIP_ACTIVE
};
State currentState = WAITING_FOR_COMMAND;

unsigned long lastCommandCheck = 0;
const unsigned long COMMAND_CHECK_INTERVAL = 3000; // 3 segundos

void setup() {
    Serial.begin(115200);
    SPI.begin();
    mfrc522.PCD_Init();

    Serial.println("\n=== SISTEMA INTERFLOW - SCANNER ===");
    Serial.print("Bus ID: ");
    Serial.println(BUS_ID);

    // Conectar WiFi
    WiFi.begin(ssid, password);
    Serial.print("Conectando a WiFi");

    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }

    Serial.println("\n✅ WiFi Conectado");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    Serial.println("Esperando comandos del servidor...\n");
}

void loop() {
    // Verificar comandos del servidor cada 3 segundos
    if (millis() - lastCommandCheck >= COMMAND_CHECK_INTERVAL) {
        lastCommandCheck = millis();
        checkServerForCommands();
    }

    // Si hay viaje activo, procesar tarjetas
    if (currentState == TRIP_ACTIVE) {
        if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
            String cardUID = getCardUID();
            processPayment(cardUID);
            mfrc522.PICC_HaltA();
        }
    }

    delay(100);
}

void checkServerForCommands() {
    std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
    client->setInsecure(); // ⚠️ Para producción, validar certificado

    HTTPClient http;

    String url = String(server_url) + "/api/device/command/" + String(BUS_ID);

    if (http.begin(*client, url)) {
        int httpCode = http.GET();

        if (httpCode == 200) {
            String payload = http.getString();

            StaticJsonDocument<512> doc;
            DeserializationError error = deserializeJson(doc, payload);

            if (!error) {
                const char* command = doc["command"];

                if (String(command) == "start_trip") {
                    current_command_id = doc["command_id"] | 0;
                    active_trip_id = doc["trip_id"] | 0;

                    if (active_trip_id > 0) {
                        Serial.println("\n🚌 COMANDO RECIBIDO: INICIAR VIAJE");
                        Serial.print("Trip ID: ");
                        Serial.println(active_trip_id);

                        currentState = TRIP_ACTIVE;
                        markCommandAsCompleted();
                    }
                }
                else if (String(command) == "end_trip") {
                    current_command_id = doc["command_id"] | 0;

                    Serial.println("\n🛑 COMANDO RECIBIDO: FINALIZAR VIAJE");

                    active_trip_id = 0;
                    currentState = WAITING_FOR_COMMAND;
                    markCommandAsCompleted();
                }
                // Si command == "none", no hacer nada (mantener estado actual)
            }
        }

        http.end();
    }
}

void markCommandAsCompleted() {
    std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
    client->setInsecure();

    HTTPClient http;

    String url = String(server_url) + "/api/device/command/" + String(current_command_id) + "/complete";

    if (http.begin(*client, url)) {
        http.addHeader("Content-Type", "application/json");

        int httpCode = http.POST("{}");

        if (httpCode == 200) {
            Serial.println("✅ Comando completado");
        }

        http.end();
    }
}

void processPayment(String cardUID) {
    Serial.println("\n💳 TARJETA DETECTADA");
    Serial.print("UID: ");
    Serial.println(cardUID);

    if (active_trip_id == 0) {
        Serial.println("⚠️ ERROR: No hay viaje activo");
        return;
    }

    std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
    client->setInsecure();

    HTTPClient http;

    String url = String(server_url) + "/api/payment/process";

    if (http.begin(*client, url)) {
        http.addHeader("Content-Type", "application/json");

        StaticJsonDocument<256> doc;
        doc["uid"] = cardUID;
        doc["trip_id"] = active_trip_id;

        String jsonPayload;
        serializeJson(doc, jsonPayload);

        Serial.println("📤 Procesando pago...");

        int httpCode = http.POST(jsonPayload);

        if (httpCode == 200) {
            String response = http.getString();

            StaticJsonDocument<512> responseDoc;
            deserializeJson(responseDoc, response);

            const char* status = responseDoc["status"];

            if (String(status) == "success") {
                Serial.println("✅✅✅ PAGO EXITOSO ✅✅✅");
                Serial.print("Nuevo saldo: ");
                Serial.println(responseDoc["new_balance"].as<String>());
            } else {
                Serial.println("❌ PAGO RECHAZADO");
                Serial.println(responseDoc["message"].as<String>());
            }
        } else {
            Serial.print("❌ Error HTTP: ");
            Serial.println(httpCode);
        }

        http.end();
    }

    delay(2000);
}

String getCardUID() {
    String uid = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
        if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
        uid += String(mfrc522.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    return uid;
}
```

---

## 5.3 Configurar cada Arduino (IMPORTANTE)

Cada bus necesita su propia configuración:

### Scanner del Bus 1:
```cpp
const char* ssid = "WiFi_Chofer_1";
const char* password = "12345678";
const char* server_url = "https://interflow-backend.onrender.com";
const int BUS_ID = 1;
```

### Scanner del Bus 2:
```cpp
const char* ssid = "WiFi_Chofer_2";
const char* password = "12345678";
const char* server_url = "https://interflow-backend.onrender.com";
const int BUS_ID = 2;
```

Y así sucesivamente...

---

## 5.4 Subir el código al Arduino

1. Conecta el ESP8266 a tu PC vía USB
2. En Arduino IDE:
   - Herramientas → Placa → NodeMCU 1.0 (ESP-12E Module)
   - Herramientas → Puerto → (selecciona el puerto COM)
3. Click en "Subir" (flecha →)
4. Espera a que termine (30-60 segundos)

---

## 5.5 Configurar WiFi del chofer

El chofer debe compartir internet desde su celular:

**En Android:**
1. Ajustes → Red e Internet → Zona WiFi portátil
2. Activar "Zona WiFi portátil"
3. Configurar:
   - Nombre: `WiFi_Chofer_1` (o el que programaste)
   - Contraseña: `12345678` (o la que programaste)
4. Activar

El Arduino se conectará automáticamente.

---

## ✅ CHECKPOINT 5

Verifica con el Monitor Serial (Herramientas → Monitor Serie, 115200 baud):

- [ ] Ves "WiFi Conectado"
- [ ] Ves una IP asignada (ej: 192.168.43.2)
- [ ] Ves "Esperando comandos del servidor..."
- [ ] NO ves errores de conexión

Si TODO está bien, continúa al Paso 6.

---

# 6. PRUEBAS FINALES END-TO-END

⏱️ **Tiempo:** 1 hora
🎯 **Objetivo:** Verificar que TODO el sistema funcione completo desde la nube

---

## 6.1 Checklist de Verificación General

### ☁️ SERVICIOS EN LA NUBE

- [ ] **Railway (Base de Datos)**
  - Ve a https://railway.app
  - Tu MySQL está "Active"
  - Puedes ver las tablas en la pestaña "Data"

- [ ] **Render (Backend)**
  - Ve a https://render.com
  - Tu servicio está "Live" (verde)
  - No hay errores en los "Logs"
  - URL funciona: `https://TU-APP.onrender.com`

- [ ] **Panel Admin accesible**
  - Abre: `https://TU-APP.onrender.com/admin`
  - Puedes iniciar sesión
  - Puedes ver usuarios, buses, rutas

---

## 6.2 Prueba Completa del Flujo (Paso a Paso)

### PRUEBA 1: Pasajero usa la app móvil

**Paso 1:** En tu celular, abre Expo Go y carga la app

**Paso 2:** Click en "Ingresar como Pasajero"

**Paso 3:** Inicia sesión con un usuario passenger

**Paso 4:** Deberías ver:
- Tu nombre de usuario
- Tu saldo
- Dashboard de pasajero

✅ **Pasajero puede acceder**

---

### PRUEBA 2: Chofer usa la app móvil

**Paso 1:** En otro celular (o cierra sesión), click en "Ingresar como Chofer"

**Paso 2:** Inicia sesión con un usuario driver

**Paso 3:** Deberías ver:
- Tu nombre
- "Sin viaje activo"
- Dashboard de chofer

✅ **Chofer puede acceder**

---

### PRUEBA 3: Flujo completo (LA PRUEBA DEFINITIVA)

Esta es la prueba MÁS IMPORTANTE. Vamos a simular un viaje completo:

**PREPARACIÓN:**
1. Chofer activa el hotspot de su celular (WiFi_Chofer_1, contraseña: 12345678)
2. Arduino conectado y encendido (verifica en Monitor Serial que se conectó)
3. Chofer con la app móvil abierta

**PASO A PASO:**

1. **Chofer inicia viaje** (desde la app móvil - versión completa futura)
   - Por ahora, hazlo desde el panel admin web:
   - `https://TU-APP.onrender.com/admin/trips`
   - Crea un nuevo trip con el bus del chofer

2. **Arduino recibe el comando**
   - Verifica en Monitor Serial que aparezca:
   ```
   🚌 COMANDO RECIBIDO: INICIAR VIAJE
   Trip ID: X
   ```

3. **Pasajero acerca tarjeta al lector RFID**
   - El Arduino debería mostrar:
   ```
   💳 TARJETA DETECTADA
   UID: XXXXXXXX
   📤 Procesando pago...
   ✅✅✅ PAGO EXITOSO ✅✅✅
   Nuevo saldo: XX.XX
   ```

4. **Verifica en el panel admin**
   - Ve a `https://TU-APP.onrender.com/admin/transactions`
   - Deberías ver la transacción nueva del pago

5. **Pasajero ve la notificación** (versión completa futura)
   - En la app móvil del pasajero debería aparecer notificación de pago

6. **Chofer finaliza viaje**
   - Por ahora desde panel admin:
   - `https://TU-APP.onrender.com/admin/trips`
   - Edita el trip y marca como finalizado

7. **Arduino recibe comando de finalizar**
   - Monitor Serial muestra:
   ```
   🛑 COMANDO RECIBIDO: FINALIZAR VIAJE
   ```

✅ **SI TODO ESTO FUNCIONA = MIGRACIÓN EXITOSA!**

---

## 6.3 Checklist Final Completo

### Panel Admin (Web)
- [ ] Puedo acceder a `https://TU-APP.onrender.com/admin`
- [ ] Puedo crear usuarios
- [ ] Puedo crear/editar buses
- [ ] Puedo crear/editar rutas
- [ ] Puedo ver viajes en tiempo real
- [ ] Puedo ver transacciones
- [ ] Puedo recargar saldo a tarjetas

### App Móvil (Pasajero)
- [ ] Puedo descargar desde Expo Go
- [ ] Veo pantalla de bienvenida
- [ ] Puedo iniciar sesión
- [ ] Veo mi saldo
- [ ] Puedo cerrar sesión

### App Móvil (Chofer)
- [ ] Puedo iniciar sesión
- [ ] Veo dashboard de chofer
- [ ] Puedo cerrar sesión

### Arduino (Hardware)
- [ ] Se conecta a WiFi del chofer
- [ ] Se conecta al servidor en la nube
- [ ] Recibe comandos de iniciar viaje
- [ ] Procesa pagos con tarjetas RFID
- [ ] Envía confirmación al servidor
- [ ] Recibe comandos de finalizar viaje

### Comunicación End-to-End
- [ ] Chofer inicia viaje → Arduino recibe comando
- [ ] Pasajero paga → Se registra en BD
- [ ] Se actualiza saldo del pasajero
- [ ] Chofer finaliza viaje → Arduino recibe comando

---

## 6.4 ¿Qué hacer si algo falla?

Ve al Paso 7 (Problemas Comunes) para soluciones.

Si TODO funciona, ¡FELICIDADES! Tu sistema está en producción.

---

# 7. PROBLEMAS COMUNES Y SOLUCIONES

---

## Problema 1: Render dice "Build failed"

**Síntomas:**
- Render muestra error rojo
- Logs muestran error durante el build

**Soluciones:**

1. **Verifica el Dockerfile**
   - Asegúrate de que lo copiaste EXACTAMENTE como está en el documento
   - Sin espacios extras o líneas faltantes

2. **Revisa las variables de entorno**
   - En Render → Environment
   - Verifica que TODAS las variables estén configuradas
   - Especialmente `APP_KEY`, `DB_HOST`, `DB_PORT`, etc.

3. **Mira los logs**
   - En Render → Logs
   - Busca la línea específica del error
   - Generalmente dice "ERROR: ..." o "FAILED: ..."

---

## Problema 2: Arduino no se conecta a WiFi

**Síntomas:**
- Monitor Serial muestra "Conectando a WiFi......" infinitamente
- Nunca muestra "WiFi Conectado"

**Soluciones:**

1. **Verifica el nombre y contraseña de WiFi**
   ```cpp
   const char* ssid = "WiFi_Chofer_1";  // ← debe coincidir EXACTAMENTE
   const char* password = "12345678";
   ```

2. **Verifica que el hotspot esté activo**
   - En el celular del chofer
   - Ajustes → Zona WiFi portátil
   - Debe estar ACTIVADO

3. **Verifica que el celular tenga datos móviles**
   - El hotspot necesita internet activo

4. **Reinicia el Arduino**
   - Desconecta y vuelve a conectar
   - O presiona el botón RESET

---

## Problema 3: Arduino se conecta pero no recibe comandos

**Síntomas:**
- Monitor Serial muestra "WiFi Conectado"
- Pero nunca muestra "COMANDO RECIBIDO"

**Soluciones:**

1. **Verifica la URL del servidor**
   ```cpp
   const char* server_url = "https://interflow-backend.onrender.com";  // ← HTTPS, no HTTP
   ```

2. **Verifica que el servidor esté funcionando**
   - Abre en navegador: `https://TU-APP.onrender.com`
   - Debe cargar (no error 404 o 500)

3. **Verifica el BUS_ID**
   ```cpp
   const int BUS_ID = 1;  // ← debe existir en la base de datos
   ```
   - Ve al panel admin → Buses
   - Verifica que exista un bus con ese ID

4. **Crea un comando manualmente**
   - Panel admin → Bus Commands
   - Crea un nuevo comando "start_trip" para ese bus
   - Observa si el Arduino lo recibe

---

## Problema 4: App móvil no carga / Pantalla blanca

**Síntomas:**
- Escaneas QR con Expo Go
- Pantalla blanca o error

**Soluciones:**

1. **Verifica que `npx expo start` esté corriendo**
   - En PowerShell debería mostrar el QR
   - Si no, ejecuta de nuevo

2. **Verifica que tu PC y celular estén en la MISMA red WiFi**
   - Ambos conectados al mismo router

3. **Revisa errores en la consola**
   - En PowerShell donde corre Expo
   - Busca líneas rojas de error

4. **Reinstala dependencias**
   ```powershell
   cd C:\Users\brand\OneDrive\Escritorio\interflow-mobile
   rm -r node_modules
   npm install
   npx expo start
   ```

---

## Problema 5: Login en app móvil falla

**Síntomas:**
- Ingresas email/password
- Dice "Error al iniciar sesión"

**Soluciones:**

1. **Verifica la URL en `config.js`**
   ```javascript
   export const API_BASE_URL = 'https://interflow-backend.onrender.com';  // ← sin / al final
   ```

2. **Verifica que el usuario exista en la BD**
   - Panel admin → Usuarios
   - Verifica que el email y rol sean correctos

3. **Verifica CORS en Laravel**
   - `config/cors.php` debe permitir tu dominio
   - O temporalmente permite todo: `'allowed_origins' => ['*']`

4. **Revisa los logs de Render**
   - Ve a Render → Logs
   - Busca la petición de login
   - Verifica si hay error 500 o similar

---

## Problema 6: Panel admin funciona pero API no

**Síntomas:**
- Puedes acceder a `https://TU-APP.onrender.com/admin`
- Pero API devuelve 404 o 500

**Soluciones:**

1. **Ejecuta cache de rutas**
   - Render → Shell
   ```bash
   php artisan route:cache
   php artisan config:cache
   ```

2. **Verifica que las rutas API existan**
   - `routes/api.php` debe tener las rutas
   - `/api/cliente/login`, `/api/payment/process`, etc.

---

## Problema 7: Pagos no se procesan

**Síntomas:**
- Arduino detecta tarjeta
- Pero dice "PAGO RECHAZADO" o error HTTP

**Soluciones:**

1. **Verifica que haya un viaje activo**
   - Panel admin → Trips
   - Debe haber un trip sin finalizar para ese bus

2. **Verifica el saldo de la tarjeta**
   - Panel admin → Cards
   - La tarjeta debe tener saldo suficiente

3. **Verifica el UID de la tarjeta**
   - Monitor Serial muestra el UID detectado
   - Panel admin → Cards
   - El UID debe coincidir EXACTAMENTE (mayúsculas/minúsculas)

---

## 📞 ¿Necesitas más ayuda?

Si ninguna de estas soluciones funciona:

1. **Revisa los logs detalladamente:**
   - Render → Logs (backend)
   - Arduino → Monitor Serial (hardware)
   - Expo → Consola en PowerShell (app móvil)

2. **Verifica las credenciales:**
   - Railway (base de datos)
   - Render (variables de entorno)
   - Arduino (WiFi, URL, BUS_ID)

3. **Prueba paso a paso:**
   - No pruebes todo junto
   - Primero verifica que cada componente funcione solo
   - Luego prueba la integración

---

# 🎉 FELICIDADES!

Si llegaste hasta aquí y TODO funciona, has completado exitosamente la migración de tu sistema INTERFLOW a producción.

## ✅ Lo que lograste:

- ✅ Base de datos MySQL en la nube (Railway)
- ✅ Backend Laravel + Panel Admin en internet (Render)
- ✅ App móvil unificada para Choferes y Pasajeros (Expo)
- ✅ Arduino conectándose al servidor vía HTTPS
- ✅ Sistema completo funcionando end-to-end

## 🚀 Próximos pasos (opcionales):

1. **Completar dashboards de la app móvil**
   - Agregar todas las funcionalidades completas
   - Listar viajes, solicitar devoluciones, etc.

2. **Generar APK de la app**
   - Para instalar sin Expo Go
   - `eas build --platform android`

3. **Agregar más funcionalidades**
   - Sistema de ubicaciones
   - Notificaciones push
   - Reportes avanzados

4. **Mejorar seguridad**
   - Device tokens para Arduino
   - Validación de certificados SSL
   - Rate limiting más estricto

---

**¡ÉXITO EN TU PROYECTO INTERFLOW!** 🚌💳
    @vite(['resources/js/app.jsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

✅ **Ya está!** El frontend se sirve desde `https://interflow-backend.onrender.com/app`

## 5.3 Opción avanzada: Deploy en Vercel separado

Si prefieres tener el frontend en Vercel:

1. Crea repo `interflow-frontend` solo con `/resources/js/*`
2. Deploy en Vercel
3. Configura `VITE_API_BASE=https://interflow-backend.onrender.com`

---

# 6. CONFIGURACIÓN MQTT (HIVEMQ) - OPCIONAL (20 min)

## 6.1 Crear cluster gratuito

1. Ve a **https://www.hivemq.com/mqtt-cloud-broker/**
2. Sign Up → Start Free
3. Create Cluster:
   - **Name:** interflow-iot
   - **Provider:** AWS
   - **Region:** us-east-1
   - **Plan:** Free (hasta 25 conexiones)

4. Obtén credenciales:
   ```
   Host: xxxxxxx.s1.eu.hivemq.cloud
   Port: 8883 (TLS)
   Username: interflow
   Password: xxxxxxxxx
   ```

## 6.2 Definir Topics

```
bus/{bus_id}/commands      → Backend publica comandos
bus/{bus_id}/events        → ESP publica eventos
bus/{bus_id}/status        → ESP publica heartbeat cada 30s
```

## 6.3 Instalar cliente MQTT en Laravel

```bash
composer require php-mqtt/laravel-client
```

Configurar `config/mqtt-client.php`:

```php
'connections' => [
    'hivemq' => [
        'host' => env('MQTT_HOST', 'xxxxxxx.s1.eu.hivemq.cloud'),
        'port' => env('MQTT_PORT', 8883),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'use_tls' => true,
    ],
],
```

Agregar a `.env`:

```env
MQTT_HOST=xxxxxxx.s1.eu.hivemq.cloud
MQTT_PORT=8883
MQTT_USERNAME=interflow
MQTT_PASSWORD=xxxxxxxxx
```

## 6.4 Publicar comandos desde Laravel

En `DriverActionController::requestTripStart()`:

```php
use PhpMqtt\Client\MqttClient;

public function requestTripStart(Request $request)
{
    // ... código actual ...

    $trip = Trip::create([...]);
    $command = BusCommand::create([...]);

    // Publicar comando vía MQTT
    try {
        $mqtt = new MqttClient(
            env('MQTT_HOST'),
            env('MQTT_PORT'),
            'laravel-publisher'
        );

        $mqtt->connect(
            env('MQTT_USERNAME'),
            env('MQTT_PASSWORD'),
            [], // clean session
            true // use TLS
        );

        $mqtt->publish(
            "bus/{$busId}/commands",
            json_encode([
                'command' => 'start_trip',
                'trip_id' => $trip->id,
                'driver_id' => $driver->id,
                'timestamp' => now()->toIso8601String()
            ]),
            0 // QoS
        );

        $mqtt->disconnect();
    } catch (\Exception $e) {
        \Log::error('MQTT Publish Error: ' . $e->getMessage());
        // Continuar con flujo normal (comando en BD)
    }

    return response()->json([...]);
}
```

---

# 7. ACTUALIZACIÓN ARDUINO (30-45 min)

## 7.1 Crear sistema de Device Tokens

### Migration: `create_device_tokens_table.php`

```bash
php artisan make:migration create_device_tokens_table
```

```php
Schema::create('device_tokens', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('bus_id')->unique();
    $table->string('token', 80)->unique();
    $table->string('description')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamp('last_seen_at')->nullable();
    $table->timestamps();

    $table->foreign('bus_id')->references('id')->on('buses')->onDelete('cascade');
});
```

```bash
php artisan migrate
```

### Admin: Generar tokens

En panel admin, agregar botón "Generar Token" para cada bus:

```php
// Admin/BusController.php
public function generateToken(Bus $bus)
{
    $token = Str::random(80);

    \DB::table('device_tokens')->updateOrInsert(
        ['bus_id' => $bus->id],
        [
            'token' => $token,
            'description' => "Token para {$bus->plate}",
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]
    );

    return back()->with('success', "Token generado: {$token}");
}
```

## 7.2 Middleware para validar Device Token

`app/Http/Middleware/ValidateDeviceToken.php`:

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateDeviceToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token required'], 401);
        }

        $deviceToken = \DB::table('device_tokens')
            ->where('token', $token)
            ->where('active', true)
            ->first();

        if (!$deviceToken) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Actualizar last_seen
        \DB::table('device_tokens')
            ->where('id', $deviceToken->id)
            ->update(['last_seen_at' => now()]);

        // Guardar bus_id en request
        $request->merge(['device_bus_id' => $deviceToken->bus_id]);

        return $next($request);
    }
}
```

Registrar en `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'device.token' => \App\Http\Middleware\ValidateDeviceToken::class,
];
```

Aplicar en `routes/api.php`:

```php
Route::middleware('device.token')->group(function () {
    Route::post('/payment/process', [PaymentController::class, 'process']);
    Route::get('/device/command/{bus}', [DeviceController::class, 'getCommand']);
    Route::post('/device/command/{id}/complete', [DeviceController::class, 'markCommandAsCompleted']);
});
```

## 7.3 Código Arduino ACTUALIZADO

```cpp
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <MFRC522.h>
#include <SPI.h>
#include <ArduinoJson.h>

// === CONFIGURACIÓN WiFi ===
const char* ssid = "TU_WIFI";
const char* password = "TU_PASSWORD";

// === CONFIGURACIÓN SERVIDOR ===
const char* server_url = "https://interflow-backend.onrender.com";
const char* device_token = "TU_TOKEN_AQUI"; // Generado desde panel admin

// === CONFIGURACIÓN BUS ===
const int bus_id = 1; // FIJO POR CADA SCANNER

// === PINES ===
#define SS_PIN D8
#define RST_PIN D3

// === ESTADOS ===
enum DeviceState {
    WAITING_FOR_COMMAND,
    TRIP_ACTIVE
};

DeviceState currentState = WAITING_FOR_COMMAND;

// === VARIABLES GLOBALES ===
MFRC522 rfid(SS_PIN, RST_PIN);
std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
long active_trip_id = 0;
long current_command_id = 0;
unsigned long lastCommandCheck = 0;
const long commandCheckInterval = 3000;

void setup() {
    Serial.begin(115200);
    delay(500);
    Serial.println("\n=================================");
    Serial.println(" SISTEMA DE COBRO INTERFLOW");
    Serial.println("=================================");

    SPI.begin();
    rfid.PCD_Init();
    rfid.PCD_SetAntennaGain(rfid.RxGain_max);

    // WiFi Secure (ignora validación SSL para POC)
    client->setInsecure();

    connectToWiFi();

    Serial.println("\n✅ Sistema listo");
    Serial.print("✅ Bus ID: ");
    Serial.println(bus_id);
    Serial.println(">>> MODO: ESPERANDO COMANDOS <<<\n");
}

void loop() {
    if (millis() - lastCommandCheck > commandCheckInterval) {
        checkServerForCommands();
        lastCommandCheck = millis();
    }

    switch (currentState) {
        case WAITING_FOR_COMMAND:
            break;
        case TRIP_ACTIVE:
            processPassengerPayment();
            break;
    }

    delay(50);
}

void connectToWiFi() {
    Serial.print("\n🔌 Conectando WiFi: ");
    Serial.println(ssid);
    WiFi.begin(ssid, password);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\n✅ WiFi conectado!");
        Serial.print("📍 IP: ");
        Serial.println(WiFi.localIP());
    }
}

void checkServerForCommands() {
    HTTPClient http;
    String url = String(server_url) + "/api/device/command/" + String(bus_id);

    http.begin(*client, url);
    http.addHeader("Accept", "application/json");
    http.addHeader("Authorization", "Bearer " + String(device_token));

    int httpCode = http.GET();

    if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        StaticJsonDocument<512> doc;

        if (deserializeJson(doc, payload) == DeserializationError::Ok) {
            const char* command = doc["command"] | "none";

            if (String(command) == "start_trip") {
                current_command_id = doc["command_id"] | 0;
                active_trip_id = doc["trip_id"] | 0;

                Serial.println("\n📥 COMANDO: Iniciar Viaje");
                Serial.print("🎯 Trip ID: ");
                Serial.println(active_trip_id);

                if (active_trip_id > 0) {
                    currentState = TRIP_ACTIVE;
                    Serial.println("✅ Modo COBRANDO activado");
                    markCommandAsCompleted();
                }
            }
            else if (String(command) == "end_trip") {
                current_command_id = doc["command_id"] | 0;

                Serial.println("\n📥 COMANDO: Finalizar Viaje");
                active_trip_id = 0;
                currentState = WAITING_FOR_COMMAND;
                Serial.println("✅ Modo ESPERANDO activado");
                markCommandAsCompleted();
            }
        }
    } else if (httpCode == 401) {
        Serial.println("❌ ERROR: Token inválido o expirado");
    }

    http.end();
}

void processPassengerPayment() {
    if (!rfid.PICC_IsNewCardPresent()) {
        delay(100);
        return;
    }

    if (!rfid.PICC_ReadCardSerial()) {
        Serial.println("❌ Error leyendo tarjeta");
        delay(100);
        return;
    }

    Serial.println("\n💳 TARJETA DETECTADA");

    String uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        uid += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();

    Serial.print("🆔 UID: ");
    Serial.println(uid);

    if (active_trip_id == 0) {
        Serial.println("⚠ ERROR: No hay viaje activo");
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        delay(2000);
        return;
    }

    HTTPClient http;
    String url = String(server_url) + "/api/payment/process";

    http.begin(*client, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", "Bearer " + String(device_token));

    StaticJsonDocument<256> doc;
    doc["uid"] = uid;
    doc["trip_id"] = active_trip_id;

    String body;
    serializeJson(doc, body);

    Serial.println("📤 Procesando pago...");
    Serial.print("📤 Trip ID enviado: ");
    Serial.println(active_trip_id);

    int httpCode = http.POST(body);

    if (httpCode > 0) {
        String payload = http.getString();
        StaticJsonDocument<512> response;

        if (deserializeJson(response, payload) == DeserializationError::Ok) {
            const char* status = response["status"] | "unknown";
            Serial.print("📊 Estado: ");
            Serial.println(status);

            if (String(status) == "success") {
                Serial.println("✅✅✅ PAGO EXITOSO ✅✅✅");
            } else {
                const char* message = response["message"] | "Sin mensaje";
                Serial.println("❌ PAGO RECHAZADO");
                Serial.print("❌ Razón: ");
                Serial.println(message);
            }
        }
    } else {
        Serial.print("❌ Error HTTP: ");
        Serial.println(httpCode);
    }

    http.end();
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    delay(2000);
}

void markCommandAsCompleted() {
    if (current_command_id == 0) return;

    HTTPClient http;
    String url = String(server_url) + "/api/device/command/" +
                 String(current_command_id) + "/complete";

    http.begin(*client, url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", "Bearer " + String(device_token));
    http.POST("{}");
    http.end();

    current_command_id = 0;
}
```

### Configuración por scanner:

```cpp
// === SCANNER BUS 1 ===
const char* device_token = "abc123..."; // Token del bus 1
const int bus_id = 1;

// === SCANNER BUS 2 ===
const char* device_token = "xyz789..."; // Token del bus 2
const int bus_id = 2;
```

---


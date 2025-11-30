
# Cobro Transporte

Este proyecto es un sistema de cobro y seguimiento para el transporte público. La aplicación está diseñada para ser utilizada tanto por pasajeros como por conductores, y cuenta con una aplicación móvil y un panel de administración web.

## Tecnologías Utilizadas

*   **Backend:** PHP 8.2 o superior, Laravel 10
*   **Frontend:** React 18.2, Vite, TailwindCSS
*   **Base de Datos:** MySQL o MariaDB
*   **Móvil:** Capacitor

## Instalación

1.  Clona el repositorio:

    ```bash
    git clone https://github.com/tu-usuario/cobro-transporte.git
    cd cobro-transporte
    ```

2.  Instala las dependencias de PHP:

    ```bash
    composer install
    ```

3.  Instala las dependencias de Node.js:

    ```bash
    npm install
    ```

4.  Copia el archivo de configuración de entorno:

    ```bash
    cp .env.example .env
    ```

5.  Genera una nueva clave de aplicación:

    ```bash
    php artisan key:generate
    ```

6.  Configura tu base de datos en el archivo `.env`.

7.  Ejecuta las migraciones de la base de datos:

    ```bash
    php artisan migrate
    ```

## Ejecución

1.  Inicia el servidor de desarrollo de Vite:

    ```bash
    npm run dev
    ```

2.  En otra terminal, inicia el servidor de Laravel:

    ```bash
    php artisan serve
    ```

3.  Para compilar la aplicación para producción:

    ```bash
    npm run build
    ```

4.  Para sincronizar con la plataforma móvil (Android en este caso):

    ```bash
    npx cap sync android
    ```

## Análisis del Proyecto

*   **Backend:** Framework Laravel (PHP). Utiliza `laravel/sanctum` para la autenticación. Los modelos clave incluyen `User`, `Bus`, `Ruta`, `Trip`, `Turno` y `TimeRecord`.
*   **Frontend:** Aplicación React construida con Vite. Utiliza React Router para la navegación y tiene componentes para un `PassengerDashboard`, `DriverDashboard` y un `Login` unificado. También utiliza `@capacitor/core`, lo que sugiere que está pensada para ser una aplicación móvil.
*   **API:** El archivo `routes/api.php` define los puntos finales de la API. Hay rutas separadas para conductores y pasajeros, así como rutas públicas para la interacción con dispositivos (probablemente un ESP8266 basado en la estructura de archivos). La API maneja la autenticación, la gestión de viajes, los pagos, el seguimiento de autobuses y más.
*   **Base de datos:** Las migraciones indican el esquema de la base de datos, que incluye tablas para usuarios, autobuses, rutas, viajes, turnos y registros de tiempo.

Basado en este análisis, el proyecto parece ser un sistema de pago y seguimiento del transporte público. Tiene funcionalidades tanto para pasajeros como para conductores, y parece implicar datos en tiempo real (como la ubicación de los autobuses).

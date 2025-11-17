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
    # Instalar MySQL client (para que el contenedor se conecte a la DB)
    libmariadb-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 20 (REQUERIDO para vite@7 y laravel-vite-plugin)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Configurar Apache: Habilitar mod_rewrite y apuntar a la carpeta public
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# --- CACHE BUSTER ---
# Este comando fuerza a Railway/Docker a ignorar cualquier cache previa 
# y ejecutar los comandos posteriores, incluyendo el WORKDIR.
RUN echo "Invaliding cache for correct file order"

# Establecer directorio de trabajo (Ahora en /var/www/html)
WORKDIR /var/www/html

# --- COPIAR Y CONFIGURAR EL PROYECTO ---

# Copiar archivos del proyecto DESPUÉS de instalar dependencias base
COPY . /var/www/html

# Instalar dependencias de PHP (El comando necesita el archivo composer.json)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de Node y compilar assets
RUN npm install --legacy-peer-deps
RUN npm run build

# --- APLICAR PERMISOS (¡AHORA LAS CARPETAS EXISTEN!) ---

# 1. Establecer el propietario de las carpetas al usuario de Apache (www-data)
# Esto es vital para que Laravel pueda escribir logs, sesiones y cache.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Dar permisos de escritura (recursivos)
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puerto 80 (por defecto para Apache)
EXPOSE 8080

# Comando de inicio
CMD ["apache2-foreground"]

# Alimentor

Sistema de planificacion nutricional. Backend API construido con Laravel 13.

## Requisitos

### Desarrollo (Linux/Mac)
- PHP 8.4
- Composer
- Node.js 20+
- MySQL 8.0

### Instalacion local con Laragon (Windows)
- [Laragon Full](https://laragon.org/download/) (incluye PHP, Composer, MySQL y Git)

## Instalacion local con Laragon

### 1. Instalar Laragon

Descargar e instalar [Laragon Full](https://laragon.org/download/index.html).

### 2. Configurar Laragon

Antes de continuar, cambiar el hostname para que use `.local`:

1. Click derecho sobre la ventana de Laragon
2. Ir a **Preferences > General**
3. En el campo **Hostname** cambiar `{name}.test` por `{name}.local`
4. Cerrar preferencias

Iniciar los servicios de Laragon haciendo click en **Start All**. Verificar que Apache/Nginx y MySQL esten activos (los iconos deben estar en verde).

### 3. Ejecutar el instalador

Descargar el archivo [`install.bat`](https://raw.githubusercontent.com/AcFallen/alimentor-laravel/main/install.bat) y guardarlo en `C:\laragon\www\`.

Abrir una terminal dentro de Laragon (**Menu > Terminal**) y ejecutar:

```bash
cd C:\laragon\www
install.bat
```

Este script automaticamente:
- Clona el repositorio en la carpeta `alimentor`
- Verifica que Git, PHP, Composer y MySQL esten disponibles
- Crea el archivo `.env` con la configuracion de Laragon
- Crea la base de datos `alimentor_laravel`
- Instala las dependencias de PHP
- Genera la clave de la aplicacion
- Ejecuta las migraciones de base de datos
- Carga los datos iniciales (tablas de alimentos, categorias, etc.)

### 4. Acceder al sistema

| Recurso | URL |
|---------|-----|
| Aplicacion | http://alimentor.local |
| Documentacion API | http://alimentor.local/docs/api |

### Credenciales por defecto

| Campo | Valor |
|-------|-------|
| Email | admin@alimentor.net.pe |
| Clave | password |

## Actualizacion

Cuando haya una nueva version disponible:

```bash
cd C:\laragon\www\alimentor
git pull
update.bat
```

El script `update.bat` actualiza las dependencias, ejecuta migraciones nuevas y limpia la cache. El frontend se actualiza automaticamente con `git pull`.

## Desarrollo

### Configuracion

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
```

### Levantar el entorno de desarrollo

```bash
composer run dev
```

Esto inicia concurrentemente: servidor Laravel, queue listener, logs (Pail) y Vite.

### Comandos utiles

```bash
# Ejecutar tests
composer run test

# Formatear codigo PHP
vendor/bin/pint

# Listar rutas de la API
php artisan route:list

# Ejecutar migraciones
php artisan migrate

# Rehacer base de datos con datos
php artisan migrate:fresh --seed
```

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

Descargar e instalar [Laragon Full](https://laragon.org/download/index.html). Asegurate de que Laragon este iniciado con Apache/Nginx y MySQL activos.

### 2. Clonar el repositorio

Abrir una terminal dentro de Laragon (Menu > Terminal) y ejecutar:

```bash
cd C:\laragon\www
git clone <URL_DEL_REPOSITORIO> alimentor-laravel
cd alimentor-laravel
```

### 3. Ejecutar el instalador

```bash
install.bat
```

Este script automaticamente:
- Verifica que PHP, Composer y MySQL esten disponibles
- Crea el archivo `.env` con la configuracion de Laragon
- Crea la base de datos `alimentor_laravel`
- Instala las dependencias de PHP
- Genera la clave de la aplicacion
- Ejecuta las migraciones de base de datos
- Carga los datos iniciales (tablas de alimentos, categorias, etc.)

### 4. Acceder al sistema

| Recurso | URL |
|---------|-----|
| Aplicacion | http://alimentor-laravel.test |
| API | http://alimentor-laravel.test/api |
| Documentacion API | http://alimentor-laravel.test/docs/api |

### Credenciales por defecto

| Campo | Valor |
|-------|-------|
| Email | admin@alimentor.net.pe |
| Clave | password |

## Actualizacion

Cuando haya una nueva version disponible:

```bash
cd C:\laragon\www\alimentor-laravel
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

## Estructura de la API

### Autenticacion
- `POST /api/login` - Iniciar sesion (devuelve token Sanctum)
- `POST /api/logout` - Cerrar sesion
- `GET /api/me` - Datos del usuario autenticado

### Alimentos
- `GET /api/foods` - Listar alimentos (paginado, filtros: search, food_table_id, food_category_id)
- `GET /api/foods/search` - Buscar alimentos por nombre y tabla (para selects)
- `GET /api/foods/{id}` - Detalle de un alimento con unidades
- `POST /api/foods` - Crear alimento
- `PUT /api/foods/{id}` - Actualizar alimento
- `DELETE /api/foods/{id}` - Eliminar alimento

### Unidades de alimento
- `GET /api/foods/{food}/units` - Listar unidades de un alimento
- `POST /api/foods/{food}/units` - Crear unidad
- `PUT /api/units/{id}` - Actualizar unidad
- `DELETE /api/units/{id}` - Eliminar unidad

### Recetas
- `GET /api/recipes` - Listar recetas (paginado)
- `GET /api/recipes/search` - Buscar recetas por nombre (para selects)
- `GET /api/recipes/{id}` - Detalle con ingredientes y nutrientes
- `POST /api/recipes` - Crear receta con ingredientes
- `PUT /api/recipes/{id}` - Actualizar receta
- `DELETE /api/recipes/{id}` - Eliminar receta

### Planificaciones
- `GET /api/meal-plans` - Listar planificaciones
- `POST /api/meal-plans` - Crear planificacion
- `GET /api/meal-plans/{id}` - Detalle de planificacion
- `PUT /api/meal-plans/{id}` - Actualizar planificacion
- `DELETE /api/meal-plans/{id}` - Eliminar planificacion

### Items de planificacion
- `GET /api/meal-plans/{id}/meal-plan-items` - Listar items
- `POST /api/meal-plans/{id}/meal-plan-items` - Agregar item (receta o alimento)
- `GET /api/meal-plan-items/{id}` - Detalle de item
- `PUT /api/meal-plan-items/{id}` - Actualizar item
- `DELETE /api/meal-plan-items/{id}` - Eliminar item

### Endpoints especiales
- `GET /api/meal-plans/{id}/calendar?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD` - Vista calendario
- `GET /api/meal-plans/{id}/daily?date=YYYY-MM-DD` - Vista diaria con nutrientes completos

### Catalogos
- `GET /api/food-categories` - Categorias de alimentos
- `GET /api/food-tables` - Tablas de composicion de alimentos
- `GET /api/recipe-categories` - Categorias de recetas

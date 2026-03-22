@echo off
chcp 65001 >nul
title Alimentor - Instalador

echo ============================================
echo    ALIMENTOR - Instalador del Sistema
echo ============================================
echo.

REM =============================================
REM  CONFIGURACION
REM =============================================
SET REPO_URL=https://github.com/AcFallen/alimentor-laravel.git
SET FOLDER_NAME=alimentor
SET DB_NAME=alimentor_laravel
SET DB_USER=root
SET DB_PASS=

REM =============================================
REM  1. Verificar requisitos
REM =============================================
echo [1/7] Verificando requisitos...

where git >nul 2>nul || (echo ERROR: Git no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where php >nul 2>nul || (echo ERROR: PHP no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where composer >nul 2>nul || (echo ERROR: Composer no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where mysql >nul 2>nul || (echo ERROR: MySQL CLI no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)

echo    Git, PHP, Composer y MySQL encontrados.
echo.

REM =============================================
REM  2. Clonar repositorio
REM =============================================
echo [2/7] Clonando repositorio...

if exist %FOLDER_NAME% (
    echo    ERROR: La carpeta '%FOLDER_NAME%' ya existe.
    echo    Si deseas reinstalar, elimina la carpeta primero.
    pause
    exit /b 1
)

git clone %REPO_URL% %FOLDER_NAME%
if %errorlevel% neq 0 (
    echo ERROR: No se pudo clonar el repositorio.
    pause
    exit /b 1
)

echo    Repositorio clonado.
echo.

REM =============================================
REM  Entrar a la carpeta del proyecto
REM =============================================
cd /d %~dp0%FOLDER_NAME%

REM =============================================
REM  3. Configurar .env
REM =============================================
echo [3/7] Configurando archivo .env...
copy .env.example .env >nul
echo    .env creado desde .env.example
echo.

REM =============================================
REM  4. Crear base de datos
REM =============================================
echo [4/7] Creando base de datos...

if "%DB_PASS%"=="" (
    mysql -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
) else (
    mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
)

if %errorlevel% neq 0 (
    echo ERROR: No se pudo crear la base de datos. Verifica que MySQL este corriendo en Laragon.
    pause
    exit /b 1
)

echo    Base de datos '%DB_NAME%' lista.
echo.

REM =============================================
REM  5. Instalar dependencias PHP
REM =============================================
echo [5/7] Instalando dependencias de PHP...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: Fallo al instalar dependencias de PHP.
    pause
    exit /b 1
)
echo.

REM =============================================
REM  6. Generar clave de aplicacion
REM =============================================
echo [6/7] Generando clave de aplicacion...
php artisan key:generate --no-interaction
echo.

REM =============================================
REM  7. Ejecutar migraciones y datos iniciales
REM =============================================
echo [7/7] Ejecutando migraciones y cargando datos (esto puede tomar unos segundos)...
php artisan migrate --force --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al ejecutar migraciones.
    pause
    exit /b 1
)

php artisan db:seed --force --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al cargar datos iniciales.
    pause
    exit /b 1
)

php artisan optimize:clear >nul 2>nul

echo.
echo ============================================
echo    INSTALACION COMPLETADA
echo ============================================
echo.
echo    URL:   http://alimentor.local
echo    Docs:  http://alimentor.local/docs/api
echo.
echo    Usuario: admin@alimentor.net.pe
echo    Clave:   password
echo.
echo    Asegurate de que Laragon este iniciado
echo    con Apache/Nginx y MySQL activos.
echo ============================================
pause

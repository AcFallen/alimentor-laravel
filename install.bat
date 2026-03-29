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
SET PHP_MIN_VERSION=8.3

REM Flags de rollback (indican que fue creado)
SET CLONED=0
SET DB_CREATED=0

REM =============================================
REM  1. Verificar requisitos
REM =============================================
echo [1/7] Verificando requisitos...

where git >nul 2>nul || (echo ERROR: Git no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where php >nul 2>nul || (echo ERROR: PHP no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where composer >nul 2>nul || (echo ERROR: Composer no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)
where mysql >nul 2>nul || (echo ERROR: MySQL CLI no encontrado. Asegurate de que Laragon este iniciado. && pause && exit /b 1)

REM Verificar version de PHP
for /f "tokens=2 delims= " %%v in ('php -v 2^>nul ^| findstr /i "^PHP"') do (
    for /f "tokens=1,2 delims=." %%a in ("%%v") do (
        set PHP_MAJOR=%%a
        set PHP_MINOR=%%b
    )
)

if %PHP_MAJOR% LSS 8 (
    echo ERROR: Se requiere PHP %PHP_MIN_VERSION% o superior. Tu version es %PHP_MAJOR%.%PHP_MINOR%.
    echo        Puedes descargar PHP 8.4 desde https://windows.php.net/download
    echo        o instalar Laravel Herd desde https://herd.laravel.com
    pause
    exit /b 1
)
if %PHP_MAJOR% EQU 8 if %PHP_MINOR% LSS 3 (
    echo ERROR: Se requiere PHP %PHP_MIN_VERSION% o superior. Tu version es %PHP_MAJOR%.%PHP_MINOR%.
    echo        Puedes descargar PHP 8.4 desde https://windows.php.net/download
    echo        o instalar Laravel Herd desde https://herd.laravel.com
    pause
    exit /b 1
)

echo    Git, PHP %PHP_MAJOR%.%PHP_MINOR%, Composer y MySQL encontrados.
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
    goto :ROLLBACK
)

SET CLONED=1
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

REM Configurar Supabase
>>".env" echo SUPABASE_URL=https://abqdfednhtzzjeckmosc.supabase.co
>>".env" echo SUPABASE_API_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFicWRmZWRuaHR6emplY2ttb3NjIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQ0NTc0MjAsImV4cCI6MjA5MDAzMzQyMH0.xrnCOL-DW-U9AVO95mvsaZV0b1AeQyjoBoH1Na2lDw8

echo    .env creado y configurado.
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
    goto :ROLLBACK
)

SET DB_CREATED=1
echo    Base de datos '%DB_NAME%' lista.
echo.

REM =============================================
REM  5. Instalar dependencias PHP
REM =============================================
echo [5/7] Instalando dependencias de PHP...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: Fallo al instalar dependencias de PHP.
    goto :ROLLBACK
)
echo.

REM =============================================
REM  6. Generar clave de aplicacion
REM =============================================
echo [6/7] Generando clave de aplicacion...
php artisan key:generate --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al generar la clave de aplicacion.
    goto :ROLLBACK
)
echo.

REM =============================================
REM  7. Ejecutar migraciones y datos iniciales
REM =============================================
echo [7/7] Ejecutando migraciones y cargando datos (esto puede tomar unos segundos)...
php artisan migrate --force --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al ejecutar migraciones.
    goto :ROLLBACK
)

php artisan db:seed --force --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al cargar datos iniciales.
    goto :ROLLBACK
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
echo    Registrate desde la aplicacion para obtener acceso.
echo.
echo    Asegurate de que Laragon este iniciado
echo    con Apache/Nginx y MySQL activos.
echo ============================================
pause
exit /b 0

REM =============================================
REM  ROLLBACK - Limpieza en caso de error
REM =============================================
:ROLLBACK
echo.
echo ============================================
echo    ROLLBACK - Deshaciendo cambios...
echo ============================================

REM Volver al directorio original antes de eliminar
cd /d %~dp0

if %DB_CREATED% EQU 1 (
    echo    Eliminando base de datos '%DB_NAME%'...
    if "%DB_PASS%"=="" (
        mysql -u %DB_USER% -e "DROP DATABASE IF EXISTS %DB_NAME%;" 2>nul
    ) else (
        mysql -u %DB_USER% -p%DB_PASS% -e "DROP DATABASE IF EXISTS %DB_NAME%;" 2>nul
    )
    echo    Base de datos eliminada.
)

if %CLONED% EQU 1 (
    echo    Eliminando carpeta '%FOLDER_NAME%'...
    rmdir /s /q %FOLDER_NAME% 2>nul
    echo    Carpeta eliminada.
)

echo.
echo    Rollback completado. Ningun archivo residual quedo en el sistema.
echo    Revisa el error anterior e intenta nuevamente.
echo ============================================
pause
exit /b 1

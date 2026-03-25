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

REM Configurar credenciales de Google Sheets
>>".env" echo GOOGLE_SHEETS_SHEET_ID=1Ubu_bgC1ObfHcbowDKkgDEHvZWq5wBVZzSZFmPL2-oY
>>".env" echo GOOGLE_SERVICE_ACCOUNT_EMAIL=alimentor-local-licencias@alimentor.iam.gserviceaccount.com
>>".env" echo GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCijU4VKBW6CHzL\nM2Zx06qJ3lqV7eHx+6TLhVy9o/QqLhol8kbfaVtwm0V7n7CITCZavSOld00QxKsa\nC0yZ+hbELEFqYQLVyIbjNZ4tvyC5FyYRTIDp5JJ3tyclRhlqA4RREvMJ4wkisu1F\nw7dwR8MM6ojeAweJiu+vK46vaeQ8E66C4J5qqdRu7EoKabguWNA78RBqnC3dntYP\n3OM8bUWeB1gdYwA56mPnZlyXDIyif7n11CVjKHEypE9l5QmYULSvo4KvimDTJFBV\n4wrhD4eMNYGFW+EaT0kbnMSeNdGQ3GBIGbH2AWmK1ztoEQKVHYfEJI7MtyFhWull\nCRNhSM9jAgMBAAECggEAEGdoKNRpv44EYfvm67fA7wwvDLr74Tqpj8GpRpiuwY7z\neT7bjhzeFcgQRACChSQCKYgKz95GkFmjLAwAh/gSp2tmkpYFqONapr/jcu8Qc1Kg\nYdXcRejuKwH4WQJGUEot5oHzYDJhhUyUi0TL/0W5VlVYyJF6XOlWH3xVeDhHHb90\nMiNh6ghkk2JxNAzQC8hFOted3a5UbVzIqL14QHEK4FcypHrzEMSdobOy4MVhYaj3\n6hfGaocjuZa5VRqZlzVqpRktBMJXy1Y/y1AUKLU82BzCE3saigNdPtp1/lleYKkh\n5HSnS9PUNg9jvgSjE/bWwwCshNWJMwHTBn3CkfGMEQKBgQDSXWmZJzBOOv06RuAp\n8FYTNr3F5SiQlYl8SbiBbsFsPsS94Qf/yPRdZcgr06lEn1cn9gWSb6jj+ESssGg0\nQrOuBE3ABJMCyrz2+n5IqTJZx2jlKH+JWdS5V7uf8wY/L9PaoL8t0fjEXTngaarN\nepWtx2DkiSADw/YTxSAWVza4OwKBgQDF0JusvF9zS9rBh4OQq7I2gbC6c9hNSNy/\nCfdJsSHem88gBaoSyWhaQe5Rnu2T/nllRsvHv+sFNOlL1GRUjF8jWDyIBzSor8Y0\nJmhaZkJaMRE4Kjyo5ELnG29PSxLL9kvdciXla6NiYsac1Sx6Q5O06VeMELNOeABV\nxhJC6xL6+QKBgD0uFawJDa9y0HrGaiNIVoA4B3EqeGW0V8vh1NsvzukgSC/A5oap\ndwhCtbipUi02+i4RCwXPm5rRdYeTtnqce408izAxJGBHfjWGHHwdWRtrN0KOSKk1\nivxsW5DlKQfvbPnEjlVRH7xcMJznnlksMaPcvH06tCjkMQkG55IXwz5JAoGAJWga\nWCLTBfF9L4WZunzNWYNS0R9g8tRpcfLHgXbuibL7CvonPCA8DH1VPLgKAydm+2DU\n3jQLlFN6Hm3OfzKANyXTZIHAUnnSyD/PEfEucPHAaNeL2wA3Ko7EkMEIj+tGU1zn\nj6e4IL2/Ax9IpuIqh1ZsyL7LuXX10kJ/Z4oeb7kCgYEApFhCrV09wpq8GkOrAYZ7\nXjjRWRJv8B7PUsWIVCBWnjx8lZMB7i3whZMLNbaYEZW2aJW4WZLSctjQ7II3yCBb\nVwW4aqbOApIbxyi+US49DSzZINv9NpWZT+zjNoCHO8EJ8lklnqLNgBeIBYwXWyee\n09mKNHeTYVPJJSFzBsRNwpU=\n-----END PRIVATE KEY-----"

echo    .env creado y configurado con credenciales de Google.
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
echo    Registrate desde la aplicacion para obtener acceso.
echo.
echo    Asegurate de que Laragon este iniciado
echo    con Apache/Nginx y MySQL activos.
echo ============================================
pause

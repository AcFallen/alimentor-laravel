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
>>".env" echo GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDPmkGGrZcKbeyh\nPi8S5pcFcgExG2SBqKp6eWbJlMRDzW5UvDOp1am+/4mQA961UzlASaFLMjOVZV1Y\n/8Ql6NGJKOF8cowBCMWKGx35q9AJXYBw1se2JKQ6vEh6M4WoruruJA8+ROwLus0V\nkHCZVh9/QNVzJ1pAZya9yVDqwhtAEstbR9XmQxMwmCtA/w+8uYf6FiaMtK6Z2wE2\n0e3bWk2/z/ev3Qz6k/SZed5PKxLF5QR5JQoY1cAlZVoPhb/kNxDBf/cEP5doJJNg\nu7q718q6S/fWd18n6uXsjXz7LNgIDLxEid/lhRGjqnbZ5kCSLopjrGRNmDxmDZJQ\nObhV7JNnAgMBAAECggEANcjeHxlMH1yZSLEGmxv4lmWR33ocUOc0u6RGOAV9z58i\nbXuQ2vhLEPu9VP1brpiQJZKt4gBwksz/ITaqR7QIzUM1H/vBP+dY3k3mNzAHJtX9\nl75dYVge4ES1dktw3mHq5aWb+WL70JIl3edc9Kz2Xg6a/kl8vWC8DcFhYGDHlJKS\nHGIKayjZKnI16uM07iJHRGI0DloLS99IQxhwN9VRKFkyJ3UnjMmR+Q2ZDSjGTB5v\nYcPYlxT3NH8w/WUZfvHv07o+9gmqjdtOOJvBzhU2wHWxbbPr3kipu3Omhz4/2zxw\nBiqMUqtQ+gpBnBZt8i/o4x4ivuyqNjmFePruMmHxKQKBgQD5yiFT9T0yCC0FIV1H\nphzSd3vNBK2R7GZwcIBWkls/MIJ9b8/bxais4PmGJAK2EUrY16W0bpfWAjHOokBu\nRyi06z2iyXX15dPV0TA1D84M/94GL3htOv3fgujuaOnBDA+kkAVOJs29dgSVxDQV\nqKkHog+w3yKfP4wzlUXtvnCU/QKBgQDUw5zDVFGrQGauNgzdSDa79A27FtMlNnC4\nACFSxOUMZMdmuum63VzNSE0rg0m4vWV7doIWpHGYhCxrtcq9pIsFw82egw84L2gY\nIdtduB1juIXjfRcivkDPaI0sDpBcNnCBrOkCBgH7jDXQV/X0kFNU9zZMA6y8JrxM\nGiFSleoJMwKBgQDjUEa0GODvvvD5UjuJEGn6PjGziSZLPU6b1EV2gwn2nzag4DfQ\nUDgH21Q39l1hQqtSWiy53krevnCFErJ/qNIqkkks8xkeBWCZnBy6rP9eZRqvllOJ\nU73kwUUocOEIhOlXYJjzXeytFdFmWX7sluf7wkd1NhpTwYjdCGLyz/O0IQKBgQCc\n6+ki532UoNsagiLa0fgMh+PYqOzx9UUNcIsjULTefzXSPulEOR/JCBpijWJCLu46\nMR2hNYfSxSk+B8aQOFuQ1OlCj8cZ2V0c71urs695bQ7Syd/WcBu83Y/BfrxaRoyU\nF8ODCotLzA2krDtrNUdA01PonI05+BfIyTzqfEcwoQKBgQC51oF/88ODOEkzzVgh\nY+vkdzMk97clE6f31Y2817K35gA5wevkfyTE4XDt7EXUSUbnp4C4y65UI5dUSIB/\ngXxWohKk1rHgoofJVz+kkLvsWvM/h2I+mG8/BEq9GSE6EawacsJJEhI0n6He0IPj\nOtj7Sy6rrBScj76iZSSNMKz2Cg==\n-----END PRIVATE KEY-----\n"

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

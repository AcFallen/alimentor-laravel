@echo off
chcp 65001 >nul
title Alimentor - Actualizador

echo ============================================
echo    ALIMENTOR - Actualizador del Sistema
echo ============================================
echo.

REM =============================================
REM  1. Actualizar dependencias PHP
REM =============================================
echo [1/3] Actualizando dependencias de PHP...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: Fallo al actualizar dependencias de PHP.
    pause
    exit /b 1
)
echo.

REM =============================================
REM  2. Ejecutar migraciones nuevas
REM =============================================
echo [2/3] Ejecutando migraciones...
php artisan migrate --force --no-interaction
if %errorlevel% neq 0 (
    echo ERROR: Fallo al ejecutar migraciones.
    pause
    exit /b 1
)
echo.

REM =============================================
REM  3. Limpiar cache
REM =============================================
echo [3/3] Limpiando cache...
php artisan optimize:clear
echo.

echo ============================================
echo    ACTUALIZACION COMPLETADA
echo ============================================
echo.
echo    El frontend se actualizo con git pull.
echo    Las migraciones nuevas fueron aplicadas.
echo.
echo    URL:   http://alimentor.local
echo    API:   http://alimentor.local/api
echo    Docs:  http://alimentor.local/docs/api
echo ============================================
pause

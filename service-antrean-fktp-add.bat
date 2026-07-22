@echo off
setlocal
title Service Add Antrean FKTP
cd /d "%~dp0"

if not exist "artisan" (
    echo [ERROR] File artisan tidak ditemukan.
    echo Pastikan file BAT berada di folder utama proyek Laravel.
    goto :finish
)

if not exist ".env" (
    echo [ERROR] File .env tidak ditemukan.
    echo Konfigurasi Laravel harus disiapkan terlebih dahulu.
    goto :finish
)

set "PHP_OVERRIDE=%PHP_BIN%"
set "PHP_BIN="

if defined PHP_OVERRIDE call :try_php "%PHP_OVERRIDE%"
if defined PHP_BIN goto :php_found

call :try_php "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
if defined PHP_BIN goto :php_found

for /f "delims=" %%I in ('where php 2^>nul') do call :try_php "%%I"
if defined PHP_BIN goto :php_found

for /d %%D in ("C:\laragon\bin\php\php-*") do (
    call :try_php "%%~fD\php.exe"
)
if defined PHP_BIN goto :php_found

call :try_php "C:\xampp\php\php.exe"
if defined PHP_BIN goto :php_found

echo [ERROR] PHP dengan driver pdo_mysql tidak ditemukan.
echo Aktifkan extension pdo_mysql pada php.ini atau set PHP_BIN
echo ke lokasi php.exe yang memiliki driver MySQL.
goto :finish

:php_found
echo ==============================================
echo SERVICE ADD ANTREAN FKTP
echo Folder : %CD%
echo PHP    : %PHP_BIN%
"%PHP_BIN%" --ini
"%PHP_BIN%" -r "echo 'PDO Drivers: '.implode(',', PDO::getAvailableDrivers()).PHP_EOL;"
echo ==============================================
echo.
"%PHP_BIN%" artisan service:antrean-fktp-add

if errorlevel 1 (
    echo.
    echo [ERROR] Service berhenti karena terjadi kesalahan.
)

:finish
echo.
echo Service berhenti. Tekan tombol apa saja untuk menutup.
pause >nul
endlocal
exit /b

:try_php
if defined PHP_BIN exit /b
if not exist "%~1" exit /b
"%~1" -r "exit(in_array('mysql', PDO::getAvailableDrivers(), true) ? 0 : 1);" >nul 2>&1
if not errorlevel 1 set "PHP_BIN=%~1"
exit /b

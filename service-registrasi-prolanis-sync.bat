@echo off
setlocal
title Service Sinkron Registrasi Prolanis PRB
cd /d "%~dp0"

if not exist "artisan" (
    echo [ERROR] File artisan tidak ditemukan.
    echo Pastikan file BAT berada di folder utama proyek Laravel.
    goto :finish
)

if not exist ".env" (
    echo [ERROR] File .env tidak ditemukan.
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

for /d %%D in ("C:\laragon\bin\php\php-*") do call :try_php "%%~fD\php.exe"
if defined PHP_BIN goto :php_found

call :try_php "C:\xampp\php\php.exe"
if defined PHP_BIN goto :php_found

echo [ERROR] PHP dengan driver pdo_mysql tidak ditemukan.
goto :finish

:php_found
echo ==================================================
echo SERVICE SINKRON REGISTRASI PROLANIS / PRB
echo Folder : %CD%
echo PHP    : %PHP_BIN%
echo Interval scan: 5 detik
echo ==================================================
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$running = Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan\s+service:registrasi-prolanis-sync(?:\s|$)' }; if ($running) { exit 10 }"

if errorlevel 10 (
    echo [INFO] Service Sinkron Registrasi Prolanis / PRB sudah berjalan.
    goto :finish
)

"%PHP_BIN%" artisan cache:forget service:registrasi-prolanis-sync >nul 2>&1
"%PHP_BIN%" artisan service:registrasi-prolanis-sync --interval=5

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

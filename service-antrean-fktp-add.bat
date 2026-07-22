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

if defined PHP_BIN if exist "%PHP_BIN%" goto :php_found

for /f "delims=" %%I in ('where php 2^>nul') do set "PHP_BIN=%%I"
if defined PHP_BIN if exist "%PHP_BIN%" goto :php_found

for /d %%D in ("C:\laragon\bin\php\php-*") do (
    if exist "%%~fD\php.exe" set "PHP_BIN=%%~fD\php.exe"
)
if defined PHP_BIN if exist "%PHP_BIN%" goto :php_found

if exist "C:\xampp\php\php.exe" set "PHP_BIN=C:\xampp\php\php.exe"
if defined PHP_BIN if exist "%PHP_BIN%" goto :php_found

echo [ERROR] php.exe tidak ditemukan.
echo Tambahkan folder PHP ke PATH atau set PHP_BIN ke lokasi php.exe.
goto :finish

:php_found
echo ==============================================
echo SERVICE ADD ANTREAN FKTP
echo Folder : %CD%
echo PHP    : %PHP_BIN%
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

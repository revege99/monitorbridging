@echo off
setlocal
title Service Sinkron Registrasi Prolanis PRB

rem ============================================================
rem KONFIGURASI PROYEK - sesuaikan jika lokasi proyek berbeda
rem ============================================================
set "PROJECT_DIR=C:\laragon\www\monitorbridging"
set "MIN_PHP_VERSION=8.3"

cd /d "%PROJECT_DIR%"
if errorlevel 1 (
    echo.
    echo [ERROR] Folder proyek Laravel tidak ditemukan.
    echo Lokasi: %PROJECT_DIR%
    goto :finish
)

rem ============================================================
rem PEMERIKSAAN FILE PROYEK
rem ============================================================
if not exist "artisan" (
    echo.
    echo [ERROR] File artisan tidak ditemukan pada %CD%.
    goto :finish
)

if not exist ".env" (
    echo.
    echo [ERROR] File .env tidak ditemukan pada %CD%.
    goto :finish
)

if not exist "vendor\autoload.php" (
    echo.
    echo [ERROR] Dependency Composer belum tersedia.
    echo Jalankan: composer install --no-dev --optimize-autoloader
    goto :finish
)

rem ============================================================
rem MENCARI PHP MINIMAL 8.3 DENGAN PDO MYSQL
rem ============================================================
set "PHP_OVERRIDE=%PHP_BIN%"
set "PHP_BIN="

if defined PHP_OVERRIDE call :try_php "%PHP_OVERRIDE%"
if defined PHP_BIN goto :php_found

for /d %%D in ("C:\laragon\bin\php\php-*") do call :try_php "%%~fD\php.exe"
if defined PHP_BIN goto :php_found

for /f "delims=" %%I in ('where php 2^>nul') do call :try_php "%%I"
if defined PHP_BIN goto :php_found

call :try_php "C:\xampp\php\php.exe"
if defined PHP_BIN goto :php_found

echo.
echo [ERROR] PHP yang sesuai tidak ditemukan.
echo Persyaratan: PHP minimal %MIN_PHP_VERSION% dan extension pdo_mysql aktif.
goto :finish

:php_found
cls
echo ==================================================
echo    SERVICE SINKRON REGISTRASI PROLANIS / PRB
echo ==================================================
echo Folder proyek : %CD%
echo PHP digunakan : %PHP_BIN%
echo Interval scan : 5 detik
echo.
"%PHP_BIN%" -r "echo 'Versi PHP      : '.PHP_VERSION.PHP_EOL;"
"%PHP_BIN%" --ini
"%PHP_BIN%" -r "echo 'PDO Drivers    : '.implode(',', PDO::getAvailableDrivers()).PHP_EOL;"
echo ==================================================
echo.

rem ============================================================
rem VALIDASI ARTISAN
rem ============================================================
echo Memeriksa kompatibilitas Laravel...
"%PHP_BIN%" artisan --version >nul 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] PHP tidak kompatibel dengan proyek Laravel.
    echo Jalankan manual: "%PHP_BIN%" artisan --version
    goto :finish
)
echo [OK] PHP kompatibel dengan proyek Laravel.
echo.

rem ============================================================
rem PERIKSA APAKAH SERVICE SUDAH BERJALAN
rem ============================================================
echo Memeriksa service yang sedang berjalan...
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$running = Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan\s+service:registrasi-prolanis-sync(?:\s|$)' }; if ($running) { exit 10 } else { exit 0 }"

if errorlevel 10 (
    echo.
    echo [INFO] Service Sinkron Registrasi Prolanis / PRB sudah berjalan.
    echo Service baru tidak akan dijalankan.
    goto :already_running
)
echo [OK] Tidak ada service yang sedang berjalan.
echo.

rem ============================================================
rem MEMBERSIHKAN LOCK SERVICE INI SAJA
rem Jangan gunakan cache:clear karena dapat mengganggu service lain
rem ============================================================
echo Membersihkan lock service Prolanis sebelumnya...
"%PHP_BIN%" artisan cache:forget service:registrasi-prolanis-sync >nul 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] Lock service tidak dapat dibersihkan.
    echo Jalankan manual:
    echo "%PHP_BIN%" artisan cache:forget service:registrasi-prolanis-sync
    goto :finish
)
echo [OK] Lock service Prolanis berhasil dibersihkan.
echo.

rem ============================================================
rem MENJALANKAN SERVICE
rem ============================================================
echo ==================================================
echo Menjalankan Service Sinkron Registrasi Prolanis...
echo Tekan CTRL+C untuk menghentikan service.
echo ==================================================
echo.
"%PHP_BIN%" artisan service:registrasi-prolanis-sync --interval=5

if errorlevel 1 (
    echo.
    echo [ERROR] Service berhenti karena terjadi kesalahan.
    goto :finish
)

echo.
echo [INFO] Service Sinkron Registrasi Prolanis telah berhenti.
goto :finish_success

:already_running
echo.
echo Tekan tombol apa saja untuk menutup jendela ini.
pause >nul
endlocal
exit /b 0

:finish_success
echo.
echo Tekan tombol apa saja untuk menutup jendela ini.
pause >nul
endlocal
exit /b 0

:finish
echo.
echo Service berhenti atau tidak berhasil dijalankan.
echo Tekan tombol apa saja untuk menutup.
pause >nul
endlocal
exit /b 1

:try_php
if defined PHP_BIN exit /b 0
if not exist "%~1" exit /b 0
"%~1" -r "exit(PHP_VERSION_ID >= 80300 && in_array('mysql', PDO::getAvailableDrivers(), true) ? 0 : 1);" >nul 2>&1
if not errorlevel 1 set "PHP_BIN=%~1"
exit /b 0

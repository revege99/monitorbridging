@echo off
setlocal
title Service Satu Sehat
set "SATUSEHAT_PROJECT="
if defined SATUSEHAT_PROJECT_DIR call :try_project "%SATUSEHAT_PROJECT_DIR%"
call :try_project "%~dp0"
call :try_project "%~dp0.."
call :try_project "%CD%"
call :try_project "C:\laragon\www\monitorbridging"
call :try_project "C:\laragon\www\monitoring-bridging"
if not defined SATUSEHAT_PROJECT (
    echo [ERROR] Folder proyek Satu Sehat belum ditemukan.
    echo Lokasi BAT: "%~dp0"
    echo Letakkan BAT di folder proyek yang berisi artisan.
    echo Atau set SATUSEHAT_PROJECT_DIR ke lokasi proyek Laravel tersebut.
    goto :finish
)
pushd "%SATUSEHAT_PROJECT%"
if errorlevel 1 (
    echo [ERROR] Folder proyek tidak dapat dibuka: "%SATUSEHAT_PROJECT%"
    goto :finish
)
set "SATUSEHAT_PROJECT_OPENED=1"
echo Folder proyek: "%CD%"

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

echo [ERROR] PHP 8.3 atau lebih baru dengan driver pdo_mysql tidak ditemukan.
echo Aktifkan extension pdo_mysql pada php.ini atau set PHP_BIN
echo ke lokasi php.exe yang memiliki driver MySQL.
goto :finish

:php_found
echo ==============================================
echo SERVICE SATU SEHAT
echo Folder : %CD%
echo PHP    : %PHP_BIN%
"%PHP_BIN%" --ini
"%PHP_BIN%" -r "echo 'PDO Drivers: '.implode(',', PDO::getAvailableDrivers()).PHP_EOL;"
echo ==============================================
echo.

rem Jangan hapus lock jika worker yang sama memang masih aktif.
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ^
    "$running = Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan\s+service:satu-sehat(?:\s|$)' }; if ($running) { exit 10 }"

if errorlevel 10 (
    echo [INFO] Service Satu Sehat sudah berjalan.
    echo Tidak ada service baru yang dijalankan.
    goto :finish
)

rem Lock Satu Sehat dikelola worker; cache global tidak perlu dibersihkan.
echo Menjalankan service Satu Sehat...
echo.
"%PHP_BIN%" artisan service:satu-sehat

if errorlevel 1 (
    echo.
    echo [ERROR] Service berhenti karena terjadi kesalahan.
)

:finish
echo.
echo Service berhenti. Tekan tombol apa saja untuk menutup.
pause >nul
if defined SATUSEHAT_PROJECT_OPENED popd
endlocal
exit /b

:try_project
if defined SATUSEHAT_PROJECT exit /b
if not exist "%~1\artisan" exit /b
if not exist "%~1\app\Console\Commands\SatuSehatCommand.php" exit /b
for %%D in ("%~1") do set "SATUSEHAT_PROJECT=%%~fD"
exit /b

:try_php
if defined PHP_BIN exit /b
if not exist "%~1" exit /b
"%~1" -r "exit(PHP_VERSION_ID >= 80300 && in_array('mysql', PDO::getAvailableDrivers(), true) ? 0 : 1);" >nul 2>&1
if not errorlevel 1 set "PHP_BIN=%~1"
exit /b

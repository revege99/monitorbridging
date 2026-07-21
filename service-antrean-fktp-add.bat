@echo off
title Service Add Antrean FKTP
cd /d "%~dp0"
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan service:antrean-fktp-add
echo.
echo Service berhenti. Tekan tombol apa saja untuk menutup.
pause >nul

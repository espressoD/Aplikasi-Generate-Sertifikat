@echo off
REM 🔧 Updated 2025-12-14: Optimized for Laravel 7 + multi-page PDF generation
REM Batch file untuk menjalankan Queue Worker dengan konfigurasi optimal
REM untuk menghindari MySQL timeout errors dan memory leaks

echo ===============================================
echo   QUEUE WORKER - OPTIMIZED FOR PDF GENERATION
echo ===============================================
echo.

cd /d "C:\xampp\htdocs\Aplikasi-Generate-Sertifikat"

echo Checking Laravel environment...
php artisan --version
echo.

echo Starting Queue Worker with optimal settings:
echo - Timeout: 600 seconds (10 minutes per job)
echo - Memory: 2048MB (matches php.ini limit)
echo - Sleep: 3 seconds between jobs
echo - Tries: 3 (retry failed jobs 3 times)
echo.

echo    NOTE: Laravel 7 runs worker continuously
echo    Memory cleanup in jobs prevents leaks
echo.

echo Press Ctrl+C to stop the worker
echo.

:loop
php artisan queue:work --timeout=600 --memory=2048 --sleep=3 --tries=3
if errorlevel 1 (
    echo.
    echo   Worker crashed. Restarting in 5 seconds...
    timeout /t 5 /nobreak >nul
    goto loop
)

echo.
echo Queue Worker stopped.
pause

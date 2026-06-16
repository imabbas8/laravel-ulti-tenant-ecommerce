@echo off
REM ============================================================
REM  Bagisto - One-click start script (Windows)
REM  Starts MySQL (Laragon build) + Laravel dev server
REM ============================================================

set MYSQLD="C:\laragon\bin\mysql\mysql-8.0.40-winx64\bin\mysqld.exe"
set MYSQL_DATA=C:\laragon\data\mysql-8
set PROJECT_DIR=%~dp0

echo.
echo [1/2] Starting MySQL server on port 3306 ...
start "Bagisto MySQL" /min %MYSQLD% --datadir=%MYSQL_DATA% --port=3306 --console

REM Give MySQL a few seconds to boot
timeout /t 6 /nobreak >nul

echo [2/2] Starting Bagisto (Laravel) on http://localhost:8000 ...
cd /d "%PROJECT_DIR%"
start "Bagisto Server" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"

echo.
echo ============================================================
echo  Bagisto is starting up!
echo.
echo   Storefront : http://localhost:8000
echo   Admin Panel: http://localhost:8000/admin/login
echo.
echo   Admin Email   : admin@example.com
echo   Admin Password: admin123
echo ============================================================
echo.
echo  (Two windows opened: MySQL + PHP server. Close them to stop.)
echo.
pause

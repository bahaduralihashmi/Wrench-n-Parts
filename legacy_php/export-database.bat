@echo off
title Wrench n Parts - Database Export
color 0A

echo ============================================
echo   WRENCH N PARTS - DATABASE EXPORT
echo ============================================
echo.

set MYSQL=C:\xampp\mysql\bin\mysql.exe
set MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe
set DB_NAME=wrench_parts_db
set OUTPUT=E:\Wrench_n_Parts.sql

echo [1/3] Checking MySQL...
if not exist "%MYSQLDUMP%" (
    echo ERROR: MySQL not found at %MYSQLDUMP%
    echo Make sure XAMPP is installed at C:\xampp
    pause
    exit /b 1
)
echo OK - MySQL found
echo.

echo [2/3] Exporting database "%DB_NAME%"...
"%MYSQLDUMP%" -u root %DB_NAME% > "%OUTPUT%" 2>nul

if %errorlevel% neq 0 (
    echo ERROR: Export failed. Is XAMPP MySQL running?
    echo Start XAMPP Control Panel and click "Start" next to MySQL
    pause
    exit /b 1
)
echo OK - Database exported
echo.

echo [3/3] Cleaning up SQL file...
REM Remove first 2 lines (CREATE DATABASE and USE)
powershell -Command "$f = Get-Content '%OUTPUT%'; $f | Select-Object -Skip 2 | Set-Content '%OUTPUT%'"
echo OK - Cleaned
echo.

echo ============================================
echo   EXPORT COMPLETE!
echo ============================================
echo.
echo File saved: %OUTPUT%
echo.
echo NEXT STEPS:
echo 1. Open hosting cPanel
echo 2. Go to phpMyAdmin
echo 3. Select database "wrench_parts_db"
echo 4. Click "Import" tab
echo 5. Choose this file: %OUTPUT%
echo 6. Click "Go"
echo.
echo File size:
for %%A in ("%OUTPUT%") do echo   %%~zA bytes
echo.
pause

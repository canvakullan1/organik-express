@echo off
chcp 65001 >nul
title Organik Express - Yerel Sunucu
color 0A

rem Proje koku = bu .bat dosyasinin bir ust klasoru (scripts'in parenti)
cd /d "%~dp0.."

echo ============================================================
echo            ORGANIK EXPRESS - Yerel Sunucu
echo ============================================================
echo.

rem --- MySQL acik mi? Degilse baslat ---
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo [1/3] MySQL baslatiliyor...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe"
    timeout /t 6 /nobreak >nul
) else (
    echo [1/3] MySQL zaten calisiyor.
)

rem --- PHP'yi PATH'e ekle (XAMPP) ---
set "PATH=C:\xampp\php;%PATH%"

rem --- Tarayiciyi ~6 sn sonra ac (sunucu ayaga kalkinca) ---
echo [2/3] Tarayici birazdan acilacak...
start "" /B cmd /c "ping -n 6 127.0.0.1 >nul & explorer http://127.0.0.1:8000"

echo [3/3] Sunucu baslatiliyor...
echo.
echo  >> Site adresi : http://127.0.0.1:8000
echo  >> Yonetim     : http://127.0.0.1:8000/admin
echo.
echo  ! Bu pencereyi KAPATMAYIN - site bu pencere acik kaldikca calisir.
echo  ! Durdurmak icin: bu pencereyi kapatin veya Ctrl+C.
echo ============================================================
echo.

php artisan serve --host=127.0.0.1 --port=8000

echo.
echo Sunucu durdu. Cikmak icin bir tusa basin...
pause >nul

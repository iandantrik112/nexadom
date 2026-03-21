@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

if "%~1"=="" (
    echo.
    echo  ============================================
    echo       NexaUI Migration Runner
    echo  ============================================
    echo.
    echo   1 = run      Jalankan semua migration
    echo   2 = rollback Batalkan batch terakhir
    echo   3 = status   Lihat status migration
    echo   4 = create   Buat file migration baru
    echo.
    echo   Contoh: run, rollback, status, create CreateUsersTable
    echo.
    echo  --------------------------------------------
    set /p "input=  Masukkan perintah: "
    if "!input!"=="" (
        echo.
        echo   Batal.
        echo.
        pause
        exit /b 1
    )
    echo.
    php "%~dp0system\bin\NexaMigrate.php" !input!
) else (
    php "%~dp0system\bin\NexaMigrate.php" %*
)
echo.
pause

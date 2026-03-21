@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

if "%~1"=="" (
    powershell -ExecutionPolicy Bypass -File "%~dp0system\bin\nexa-setup.ps1"
    echo.
    echo  NexaUI CLI
    echo  ----------
    echo   nexa make [controller]    Generator controller ^(1/Product, Admin/User^)
    echo   nexa migrate [command]    Migration ^(run, rollback, status, create Name^)
    echo   nexa git [args]         Sama seperti git ^(init, add, commit, push, …^)
    echo.
    echo   Contoh:
    echo     nexa make 1/Product
    echo     nexa migrate run
    echo     nexa migrate status
    echo     nexa migrate create CreateProductsTable
    echo     nexa git init
    echo     nexa git add README.md
    echo     nexa git commit -m "first commit"
    echo.
    echo   Jika "nexa" belum dikenali:
    echo     - Muat profil PowerShell: titik spasi $PROFILE  ^(dot-source: jalankan file profil di sesi ini^)
    echo     - Atau: .\nexa make 1/Product  ^(tanpa fungsi di profil^)
    echo.
    exit /b 0
)

if /i "%~1"=="make" (
    if "%~2"=="" (
        echo.
        echo  NexaUI Controller Generator
        echo  1=Admin  2=Api  3=Frontend
        echo  Contoh: 1/Product, Admin/User
        echo.
        set /p "input=  Controller: "
        if "!input!"=="" (
            echo   Batal.
            exit /b 1
        )
        php "%~dp0system\bin\NexaMake.php" !input!
    ) else (
        php "%~dp0system\bin\NexaMake.php" %2 %3 %4 %5 %6 %7 %8 %9
    )
    exit /b 0
)

if /i "%~1"=="migrate" (
    if "%~2"=="" (
        echo.
        echo  NexaUI Migration
        echo  1=run  2=rollback  3=status  4=create
        echo.
        set /p "input=  Perintah: "
        if "!input!"=="" (
            echo   Batal.
            exit /b 1
        )
        php "%~dp0system\bin\NexaMigrate.php" !input!
    ) else (
        php "%~dp0system\bin\NexaMigrate.php" %2 %3 %4 %5 %6 %7 %8 %9
    )
    exit /b 0
)

if /i "%~1"=="git" (
    set "NEXA_GIT="
    if defined ProgramW6432 if exist "!ProgramW6432!\Git\cmd\git.exe" set "NEXA_GIT=!ProgramW6432!\Git\cmd\git.exe"
    if not defined NEXA_GIT if exist "%ProgramFiles%\Git\cmd\git.exe" set "NEXA_GIT=%ProgramFiles%\Git\cmd\git.exe"
    if not defined NEXA_GIT if exist "%ProgramFiles(x86)%\Git\cmd\git.exe" set "NEXA_GIT=%ProgramFiles(x86)%\Git\cmd\git.exe"
    if not defined NEXA_GIT if exist "%LocalAppData%\Programs\Git\cmd\git.exe" set "NEXA_GIT=%LocalAppData%\Programs\Git\cmd\git.exe"
    if not defined NEXA_GIT for /f "delims=" %%I in ('powershell -NoProfile -ExecutionPolicy Bypass -Command "(Get-ItemProperty -LiteralPath 'HKLM:\\SOFTWARE\\GitForWindows' -ErrorAction SilentlyContinue).InstallPath"') do (
        if exist "%%~I\cmd\git.exe" set "NEXA_GIT=%%~I\cmd\git.exe"
    )
    if not defined NEXA_GIT for /f "delims=" %%I in ('powershell -NoProfile -ExecutionPolicy Bypass -Command "(Get-ItemProperty -LiteralPath 'HKCU:\\SOFTWARE\\GitForWindows' -ErrorAction SilentlyContinue).InstallPath"') do (
        if exist "%%~I\cmd\git.exe" set "NEXA_GIT=%%~I\cmd\git.exe"
    )
    if not defined NEXA_GIT for /f "delims=" %%G in ('where git 2^>nul') do set "NEXA_GIT=%%G"
    if not defined NEXA_GIT (
        echo   [ERROR] Git tidak ditemukan. Pasang Git for Windows ^(centang "Git from the command line"^).
        echo   Lalu jalankan: nexa   ^(tanpa argumen^) agar Git\cmd ditambah ke PATH User, buka terminal baru, coba lagi.
        echo   Unduh: https://git-scm.com/download/win
        exit /b 1
    )
    if "%~2"=="" (
        echo.
        echo  nexa git ^<perintah yang sama seperti git.exe^>
        echo  Satu perintah per baris ^(jangan tempel dua nexa git jadi satu baris^).
        echo  Sebelum push: commit harus sukses dulu; set nama/email jika perlu:
        echo    nexa git config --global user.name "Nama Anda"
        echo    nexa git config --global user.email "email@anda.com"
        echo  Contoh urutan:
        echo    nexa git init
        echo    nexa git add README.md
        echo    nexa git commit -m "first commit"
        echo    nexa git branch -M main
        echo    nexa git remote add origin https://github.com/user/repo.git
        echo    nexa git push -u origin main
        echo.
        exit /b 1
    )
    set "GIT_CMD_DIR=!NEXA_GIT:\git.exe=!"
    set "PATH=!GIT_CMD_DIR!;%PATH%"
    set "NEXAGIT_EXE=!NEXA_GIT!"
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0system\bin\nexa-git.ps1" %*
    exit /b !errorlevel!
)

echo   [ERROR] Perintah tidak dikenal: %~1
echo   Gunakan: nexa make, nexa migrate, nexa git ...
exit /b 1

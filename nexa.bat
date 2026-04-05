@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0"

if "%~1"=="" (
    powershell -ExecutionPolicy Bypass -File "%~dp0system\bin\nexa-setup.ps1"
    echo.
    echo  NexaUI CLI
    echo  ----------
    echo   nexa make [controller]    Generator controller ^(1/Product, Admin/User^)
    echo   nexa migrate [command]    Migration ^(run, rollback, status, create Name, createdb nama_db^)
    echo   nexa start [port] [1^|2]  PHP built-in server ^(arg2: 1=localhost 2=lan, tanpa prompt^)
    echo   nexa dev [port_node]     PHP ^(jendela baru^) + Node — selesaikan ECONNREFUSED
    echo   nexa install node       Setup Node.js server ^(create server.js, package.json^)
    echo   nexa uninstall node     Remove Node.js server ^(delete server.js, package.json, node_modules^)
    echo   nexa node [port]        Node + proxy PHP ke Apache ^(PHP_SERVER=http://127.0.0.1, abaikan .nexa-php-port^)
    echo   nexa node dev [port]    Node + proxy PHP ke http://127.0.0.1:8080 ^(abaikan .nexa-php-port^)
    echo   nexa node production    Node.js server with PM2 ^(production mode^)
    echo   nexa restart [type]     Restart server ^(php, node, atau both^)
    echo   nexa stop [type]        Stop server ^(php, node, atau both^)
    echo   nexa git [args]         Sama seperti git ^(init, add, commit, push, …^)
    echo.
    echo   Contoh:
    echo     nexa make 1/Product
    echo     nexa migrate run
    echo     nexa migrate status
    echo     nexa migrate create CreateProductsTable
    echo     nexa migrate createdb nama_database
    echo     nexa start
    echo     nexa start 3000
    echo     nexa start 8000 1
    echo     nexa dev
    echo     nexa dev 4000
    echo     nexa install node
    echo     nexa uninstall node
    echo     nexa node
    echo     nexa node 4000
    echo     nexa node dev
    echo     nexa node dev 4000
    echo     nexa node production
    echo     nexa restart both
    echo     nexa stop node
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
        echo  1=run  2=rollback  3=status  4=create  5=createdb ^(nama DB^)
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

if /i "%~1"=="install" (
    if /i "%~2"=="node" (
        call "%~dp0system\bin\nexa-install-node.bat"
        exit /b !errorlevel!
    ) else (
        echo.
        echo   [ERROR] Perintah install tidak dikenal: %~2
        echo   Gunakan: nexa install node
        echo.
        exit /b 1
    )
)

if /i "%~1"=="uninstall" (
    if /i "%~2"=="node" (
        call "%~dp0system\bin\nexa-uninstall-node.bat"
        exit /b !errorlevel!
    ) else (
        echo.
        echo   [ERROR] Perintah uninstall tidak dikenal: %~2
        echo   Gunakan: nexa uninstall node
        echo.
        exit /b 1
    )
)

if /i "%~1"=="start" (
    call "%~dp0system\bin\start-server.bat" %2 %3
    exit /b !errorlevel!
)

REM PHP di jendela baru (quick localhost) lalu Node di terminal ini — port PHP dari .nexa-php-port atau 8000
if /i "%~1"=="dev" (
    if not exist "%~dp0server.js" (
        echo.
        echo  [ERROR] server.js tidak ditemukan — jalankan: nexa install node
        echo.
        exit /b 1
    )
    set "NODE_PORT=%~2"
    if "!NODE_PORT!"=="" set "NODE_PORT=3000"
    set "PHP_P="
    if exist "%~dp0.nexa-php-port" (
        set /p PHP_P=<"%~dp0.nexa-php-port"
    )
    if "!PHP_P!"=="" set "PHP_P=8000"
    where node >nul 2>&1
    if errorlevel 1 (
        echo   [ERROR] Node.js tidak ditemukan. Pasang dari https://nodejs.org/
        exit /b 1
    )
    if not exist "%~dp0node_modules" (
        echo   [INFO] npm install...
        call npm install
        if errorlevel 1 exit /b 1
    )
    echo.
    echo  ===============================================
    echo    NexaUI — dev: PHP ^(jendela baru^) + Node
    echo  ===============================================
    echo   PHP : port !PHP_P! ^(localhost^)   Node: !NODE_PORT!
    echo.
    start "NexaUI PHP" cmd /k ""%~dp0system\bin\nexa-php-window.bat" !PHP_P! 1"
    timeout /t 2 /nobreak >nul
    set "PORT=!NODE_PORT!"
    node "%~dp0server.js"
    exit /b !errorlevel!
)

if /i "%~1"=="node" (
    REM Check if server.js exists
    if not exist "%~dp0server.js" (
        echo.
        echo  ===============================================
        echo.
        echo  [ERROR] server.js tidak ditemukan
        echo.
        echo  Jalankan install terlebih dahulu:
        echo    nexa install node
        echo.
        exit /b 1
    )
    
    REM Check if second argument is "production"
    if /i "%~2"=="production" goto :node_production
    
    REM nexa node dev [port_node] — proxy ke PHP di :8080 (setara PHP_SERVER=http://127.0.0.1:8080)
    if /i "%~2"=="dev" (
        set "NEXA_IGNORE_PHP_PORT_FILE=1"
        set "PHP_SERVER=http://127.0.0.1:8080"
        set "NODE_PORT=%~3"
        if "!NODE_PORT!"=="" set "NODE_PORT=3000"
        goto :node_development_common
    )
    
    REM nexa node [port_node] — proxy ke Apache/PHP di :80 (setara NEXA_IGNORE_PHP_PORT_FILE=1 + PHP_SERVER=http://127.0.0.1)
    set "NEXA_IGNORE_PHP_PORT_FILE=1"
    set "PHP_SERVER=http://127.0.0.1"
    set "NODE_PORT=%~2"
    if "!NODE_PORT!"=="" set "NODE_PORT=3000"
    goto :node_development_common
)

goto :after_node_labels

:node_production
echo.
echo  ===============================================
echo    NexaUI - Node.js Production Mode (PM2)
echo  ===============================================
echo.

REM Check if PM2 is installed
where pm2 >nul 2>&1
if errorlevel 1 (
    echo.
    echo  [ERROR] PM2 tidak ditemukan
    echo.
    echo  Install PM2 terlebih dahulu:
    echo    npm install -g pm2
    echo.
    echo  Setelah install, restart terminal dan jalankan lagi.
    echo.
    exit /b 1
)

REM Create ecosystem.config.js if not exists
if not exist "ecosystem.config.js" (
    echo  [1/2] Creating ecosystem.config.js...
    copy /Y "%~dp0system\bin\templates\ecosystem.config.template" "ecosystem.config.js" >nul 2>&1
    if errorlevel 1 (
        echo  [ERROR] Failed to create ecosystem.config.js
        exit /b 1
    )
    echo  [OK] ecosystem.config.js created
    echo.
)

REM Start with PM2
echo  [2/2] Starting with PM2...
echo.
pm2 start ecosystem.config.js
echo.
echo  ===============================================
echo    Server started with PM2!
echo  ===============================================
echo.
echo  Perintah PM2:
echo    pm2 status              - Check status
echo    pm2 logs nexaui-node    - View logs
echo    pm2 restart nexaui-node - Restart
echo    pm2 stop nexaui-node    - Stop
echo    pm2 save                - Save config
echo.
exit /b 0

:node_development_common
where node >nul 2>&1
if errorlevel 1 (
    echo.
    echo  [ERROR] Node.js tidak ditemukan. Pasang Node.js terlebih dahulu.
    echo  Unduh: https://nodejs.org/
    echo.
    exit /b 1
)

if not exist "%~dp0node_modules" (
    echo.
    echo  [INFO] Installing Node.js dependencies...
    echo.
    call npm install
    if errorlevel 1 (
        echo.
        echo  [ERROR] Gagal menginstall dependencies.
        exit /b 1
    )
    echo.
)

echo.
echo  ===============================================
echo    NexaUI - Node.js Development Server
echo  ===============================================
echo.
echo   PHP proxy: !PHP_SERVER!  ^(NEXA_IGNORE_PHP_PORT_FILE=!NEXA_IGNORE_PHP_PORT_FILE!^)
echo   Node port: !NODE_PORT!
echo.
echo  Starting on port !NODE_PORT!...
echo.
set "PORT=!NODE_PORT!"
node "%~dp0server.js"
exit /b !errorlevel!

:after_node_labels
if /i "%~1"=="restart" (
    set "RESTART_TYPE=%~2"
    if "!RESTART_TYPE!"=="" set "RESTART_TYPE=both"
    
    echo.
    echo   Restarting servers...
    echo.
    
    if /i "!RESTART_TYPE!"=="php" (
        echo   [INFO] Restarting PHP server...
        taskkill /F /IM php.exe >nul 2>&1
        timeout /t 2 /nobreak >nul
        start "" cmd /c "%~dp0nexa.bat" start
        echo   [OK] PHP server restarted
    ) else if /i "!RESTART_TYPE!"=="node" (
        echo   [INFO] Restarting Node.js server...
        taskkill /F /IM node.exe >nul 2>&1
        timeout /t 2 /nobreak >nul
        start "" cmd /c "%~dp0nexa.bat" node 3000
        echo   [OK] Node.js server restarted
    ) else if /i "!RESTART_TYPE!"=="both" (
        echo   [INFO] Restarting PHP server...
        taskkill /F /IM php.exe >nul 2>&1
        echo   [INFO] Restarting Node.js server...
        taskkill /F /IM node.exe >nul 2>&1
        timeout /t 2 /nobreak >nul
        start "" cmd /c "%~dp0nexa.bat" start
        start "" cmd /c "%~dp0nexa.bat" node 3000
        echo   [OK] Both servers restarted
    ) else (
        echo   [ERROR] Tipe restart tidak valid: !RESTART_TYPE!
        echo   Gunakan: php, node, atau both
        exit /b 1
    )
    
    echo.
    exit /b 0
)

if /i "%~1"=="stop" (
    set "STOP_TYPE=%~2"
    if "!STOP_TYPE!"=="" set "STOP_TYPE=both"
    
    echo.
    echo   Stopping servers...
    echo.
    
    if /i "!STOP_TYPE!"=="php" (
        echo   [INFO] Stopping PHP server...
        taskkill /F /IM php.exe >nul 2>&1
        if errorlevel 1 (
            echo   [INFO] No PHP processes running
        ) else (
            echo   [OK] PHP server stopped
        )
    ) else if /i "!STOP_TYPE!"=="node" (
        echo   [INFO] Stopping Node.js server...
        
        REM Stop PM2 if exists
        where pm2 >nul 2>&1
        if not errorlevel 1 (
            pm2 stop nexaui-node >nul 2>&1
            pm2 delete nexaui-node >nul 2>&1
        )
        
        REM Stop regular Node.js processes
        taskkill /F /IM node.exe >nul 2>&1
        if errorlevel 1 (
            echo   [INFO] No Node.js processes running
        ) else (
            echo   [OK] Node.js server stopped
        )
    ) else if /i "!STOP_TYPE!"=="both" (
        echo   [INFO] Stopping PHP server...
        taskkill /F /IM php.exe >nul 2>&1
        
        echo   [INFO] Stopping Node.js server...
        
        REM Stop PM2 if exists
        where pm2 >nul 2>&1
        if not errorlevel 1 (
            pm2 stop nexaui-node >nul 2>&1
            pm2 delete nexaui-node >nul 2>&1
        )
        
        REM Stop regular Node.js processes
        taskkill /F /IM node.exe >nul 2>&1
        echo   [OK] Both servers stopped
    ) else (
        echo   [ERROR] Tipe stop tidak valid: !STOP_TYPE!
        echo   Gunakan: php, node, atau both
        exit /b 1
    )
    
    echo.
    exit /b 0
)

echo   [ERROR] Perintah tidak dikenal: %~1
echo   Gunakan: nexa make, nexa migrate, nexa start, nexa dev, nexa node, nexa restart, nexa stop, nexa git ...
exit /b 1

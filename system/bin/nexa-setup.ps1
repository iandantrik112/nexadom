# NexaUI CLI - Setup PATH + fungsi nexa di profil PowerShell
$projectPath = (Get-Item $PSScriptRoot).Parent.Parent.FullName.TrimEnd('\')
$nexaBat = Join-Path $projectPath "nexa.bat"

# 0. Git\cmd ke PATH User (agar perintah "git" di PowerShell/Cursor dikenali)
$gitCmdCandidates = @(
    (Join-Path ${env:ProgramW6432} "Git\cmd"),
    (Join-Path $env:ProgramFiles "Git\cmd"),
    (Join-Path ${env:ProgramFiles(x86)} "Git\cmd")
) | Where-Object { $_ -and (Test-Path (Join-Path $_ "git.exe")) }
if ($gitCmdCandidates.Count -gt 0) {
    $gitCmd = $gitCmdCandidates[0]
    $userPathGit = [Environment]::GetEnvironmentVariable("Path", "User")
    if ($userPathGit -notlike "*$([regex]::Escape($gitCmd))*") {
        [Environment]::SetEnvironmentVariable("Path", "$userPathGit;$gitCmd", "User")
        $env:Path = "$env:Path;$gitCmd"
        Write-Host "  [OK] Git\cmd ditambahkan ke PATH User (buka terminal baru agar semua jendela pakai PATH baru)" -ForegroundColor Green
    } else {
        Write-Host "  [OK] Git sudah di PATH User" -ForegroundColor Green
    }
} else {
    Write-Host "  [INFO] Git\cmd tidak ditemukan — pasang Git for Windows jika perlu: https://git-scm.com/download/win" -ForegroundColor DarkYellow
}

# 1. Tambah ke PATH (untuk terminal baru)
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($userPath -notlike "*$projectPath*") {
    [Environment]::SetEnvironmentVariable("Path", "$userPath;$projectPath", "User")
    Write-Host "  [OK] Project ditambahkan ke PATH" -ForegroundColor Green
} else {
    Write-Host "  [OK] Project sudah di PATH" -ForegroundColor Green
}

# 2. Pasang fungsi nexa di profil PowerShell (dinamis: cari nexa.bat di folder saat ini)
$profilePath = $PROFILE
$nexaFunc = @"
function nexa {
    `$bat = Join-Path (Get-Location) "nexa.bat"
    if (Test-Path `$bat) { & `$bat `$args }
    else { Write-Host "nexa: nexa.bat tidak ditemukan. Pastikan Anda di folder root project NexaUI." -ForegroundColor Red }
}
"@
$profileDir = Split-Path $profilePath
if (-not (Test-Path $profileDir)) { New-Item -ItemType Directory -Path $profileDir -Force | Out-Null }
$profileContent = if (Test-Path $profilePath) { Get-Content $profilePath -Raw } else { "" }
if ($profileContent -notlike "*function nexa*") {
    Add-Content -Path $profilePath -Value "`n# NexaUI CLI`n$nexaFunc"
    Write-Host "  [OK] Fungsi nexa dipasang di profil PowerShell" -ForegroundColor Green
    Write-Host "  Di terminal INI, muat profil dulu agar perintah 'nexa' dikenali:" -ForegroundColor Yellow
    Write-Host "    . `$PROFILE" -ForegroundColor Cyan
    Write-Host "  Artinya: titik = jalankan file profil sekarang; `$PROFILE = path ke Microsoft.PowerShell_profile.ps1" -ForegroundColor DarkGray
    Write-Host "  Lalu: nexa make 1/Product   (atau buka tab terminal baru)" -ForegroundColor Yellow
} else {
    Write-Host "  [OK] Fungsi nexa sudah ada di profil" -ForegroundColor Green
    if (-not (Get-Command nexa -ErrorAction SilentlyContinue)) {
        Write-Host "  Fungsi nexa belum aktif di sesi ini. Muat profil:" -ForegroundColor Yellow
        Write-Host "    . `$PROFILE" -ForegroundColor Cyan
        Write-Host "  (titik + spasi + `$PROFILE = dot-source file profil Anda)" -ForegroundColor DarkGray
        Write-Host "  Lalu: nexa make 1/Product" -ForegroundColor Yellow
    }
}

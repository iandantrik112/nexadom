# Meneruskan semua argumen ke git.exe (setelah "nexa git ...") — tanpa batas 9 token CMD.
$exe = $env:NEXAGIT_EXE
if (-not $exe -or -not (Test-Path -LiteralPath $exe)) {
    Write-Host "nexa-git: git.exe tidak valid (NEXAGIT_EXE)." -ForegroundColor Red
    exit 127
}
$gitArgs = [System.Collections.Generic.List[string]]::new()
foreach ($a in $args) { $gitArgs.Add($a) }
if ($gitArgs.Count -gt 0 -and $gitArgs[0] -ieq 'git') { $gitArgs.RemoveAt(0) }
& $exe @gitArgs
exit $LASTEXITCODE

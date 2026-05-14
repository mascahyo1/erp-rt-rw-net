# parallel-dusk.ps1
# Usage: .\parallel-dusk.ps1
# Runs Dusk tests in parallel — NO migration, single seeded DB.
# Seed DB first: php artisan setup --demo

$ErrorActionPreference = "Stop"

$folders = @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")

Write-Host "=== Parallel Dusk ($($folders.Count) workers) ===" -ForegroundColor Cyan

$hotExists = Test-Path public/hot
if ($hotExists) { Move-Item public/hot public/hot.bak -Force }

$jobs = @()
$start = Get-Date

foreach ($folder in $folders) {
    Write-Host "  Worker $folder" -ForegroundColor Yellow

    $job = Start-Job -Name "Dusk-$folder" -ScriptBlock {
        param($f)
        Set-Location $using:PWD
        Invoke-Expression "`$env:DUSK_ENABLED='true'; php artisan dusk --filter=`"$f`""
    } -ArgumentList $folder
    $jobs += $job
}

$jobs | Wait-Job | Out-Null
$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)

if ($hotExists) { Move-Item public/hot.bak public/hot -Force }

Write-Host "`n=== Results ($elapsed s) ===" -ForegroundColor Cyan
$failed = 0
foreach ($job in $jobs) {
    $output = $job | Receive-Job
    $output | ForEach-Object { Write-Host $_ }
    if ($output -match "FAIL") { $failed++ }
    $job | Remove-Job -Force
}
Write-Host "`nFailed: $failed | Time: ${elapsed}s" -ForegroundColor $(if ($failed -eq 0) { "Green" } else { "Red" })

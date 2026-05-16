# parallel-test.ps1
# Usage: .\parallel-test.ps1 [-MaxWorkers 8]
# Runs php artisan test --parallel via ParaTest

[CmdletBinding()]
param([int]$MaxWorkers = 0)

$ErrorActionPreference = "Stop"

if ($MaxWorkers -le 0) {
    Write-Host "=== Parallel Feature Test (interactive) ===" -ForegroundColor Cyan
    $input = Read-Host "Jumlah worker paralel [default 4]"
    if ($input -match '^\d+$') { $MaxWorkers = [int]$input }
    if ($MaxWorkers -lt 1) { $MaxWorkers = 4 }
} else {
    Write-Host "=== Parallel Feature Test ===" -ForegroundColor Cyan
}

Write-Host "  Workers: $MaxWorkers" -ForegroundColor Gray

$start = Get-Date
$logFile = Join-Path $PWD "tests\Browser\dusk-output\ftest-parallel.log"

$cmd = "php artisan test --parallel --processes=$MaxWorkers 2>&1"
Invoke-Expression $cmd | Tee-Object -FilePath $logFile

$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)
Write-Host "`nDone: $elapsed s" -ForegroundColor Cyan

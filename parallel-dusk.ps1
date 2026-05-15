# parallel-dusk.ps1
# Usage: .\parallel-dusk.ps1
# Runs Dusk tests in parallel — NO migration, single seeded DB.
# Seed DB first: php artisan setup --demo
# Output saved to tests/Browser/dusk-output/*.log

$ErrorActionPreference = "Stop"

$folders = @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")
$outDir = "tests\Browser\dusk-output"

Write-Host "=== Parallel Dusk ($($folders.Count) workers) ===" -ForegroundColor Cyan

$hotExists = Test-Path public/hot
if ($hotExists) { Move-Item public/hot public/hot.bak -Force }

New-Item -ItemType Directory -Force $outDir | Out-Null
Remove-Item "$outDir\*.log" -Force -ErrorAction SilentlyContinue

$jobs = @()
$start = Get-Date

foreach ($folder in $folders) {
    $logFile = "$outDir\$folder.log"

    $job = Start-Job -Name "Dusk-$folder" -ScriptBlock {
        param($f, $log)
        Set-Location $using:PWD
        $cmd = "`$env:DUSK_ENABLED='true'; php artisan dusk --filter=`"$f`" 2>&1"
        Invoke-Expression $cmd | Out-File $log -Encoding utf8
    } -ArgumentList $folder, (Resolve-Path $logFile)
    $jobs += $job
    Write-Host "  Worker $folder -> $logFile" -ForegroundColor Yellow
}

$jobs | Wait-Job | Out-Null
$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)

if ($hotExists) { Move-Item public/hot.bak public/hot -Force }

# Print summary
Write-Host "`n=== Summary ($elapsed s) ===" -ForegroundColor Cyan
$totalPassed = 0
$totalFailed = 0
foreach ($folder in $folders) {
    $logFile = "$outDir\$folder.log"
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -Raw
        $pass = ([regex]::Matches($content, '✓')).Count
        $fail = ([regex]::Matches($content, '⨯')).Count
        $status = if ($fail -eq 0) { "PASS" } else { "FAIL" }
        $color = if ($fail -eq 0) { "Green" } else { "Red" }
        Write-Host "  $folder : $status ($pass passed, $fail failed) -> $logFile" -ForegroundColor $color
        $totalPassed += $pass
        $totalFailed += $fail
    }
}
$jobs | Remove-Job -Force

$finalColor = if ($totalFailed -eq 0) { "Green" } else { "Red" }
Write-Host "`nTotal: $totalPassed passed, $totalFailed failed | Time: ${elapsed}s" -ForegroundColor $finalColor

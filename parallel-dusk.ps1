# parallel-dusk.ps1
# Usage: .\parallel-dusk.ps1 [-MaxWorkers 8] [-Folders "OperatorSaas,OperatorPerusahaan"]
# Runs Dusk tests in parallel (file-level) + generates timestamped dusk-report CSV.
# Seed DB first: php artisan setup --demo

[CmdletBinding()]
param(
    [int]$MaxWorkers = 4,
    [string]$Folders = ""
)

$ErrorActionPreference = "Stop"

$allFolders = @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")

# === Interactive prompt (if no args passed) ===
if ($MaxWorkers -eq 4 -and $Folders -eq "" -and $PSBoundParameters.Count -eq 0) {
    Write-Host "=== Parallel Dusk (interactive) ===" -ForegroundColor Cyan
    Write-Host ""

    # Portal selection
    Write-Host "Pilih portal (enter = all):" -ForegroundColor Yellow
    $i = 1
    foreach ($f in $allFolders) { Write-Host "  [$i] $f" -ForegroundColor Gray; $i++ }
    Write-Host "  [A] All" -ForegroundColor Gray
    $sel = Read-Host "Pilihan (1,2,3,4 / A)"
    if ($sel -ne "" -and $sel -ne "A" -and $sel -ne "a") {
        $allFolders = @()
        $sel -split "[,\s]+" | Where-Object { $_ -match '^\d+$' } | ForEach-Object {
            $idx = [int]$_ - 1
            if ($idx -ge 0 -and $idx -lt 4) { $allFolders += @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")[$idx] }
        }
    }

    # Worker count
    $workerInput = Read-Host "Jumlah worker paralel [default 4]"
    if ($workerInput -match '^\d+$') { $MaxWorkers = [int]$workerInput }
    if ($MaxWorkers -lt 1) { $MaxWorkers = 4 }

    Write-Host ""
} elseif ($Folders -ne "") {
    $allFolders = $Folders -split "," | ForEach-Object { $_.Trim() }
}

$outDir = "tests\Browser\dusk-output"
$baseDir = "tests\Browser\Feature"

$jenisWebMap = @{
    "OperatorSaas"        = "web operator saas"
    "OperatorPerusahaan"  = "web operator perusahaan"
    "Karyawan"            = "web karyawan"
    "Pelanggan"           = "web pelanggan"
}

$timestamp = (Get-Date).ToString("yyyyMMdd-HHmmss")
$csvFile = "$outDir\dusk-report-$timestamp.csv"

Write-Host "=== Parallel Dusk ===" -ForegroundColor Cyan
Write-Host "  MaxWorkers: $MaxWorkers" -ForegroundColor Gray
Write-Host "  Folders   : $($allFolders -join ', ')" -ForegroundColor Gray

# Discover all test files grouped by folder
$allFiles = [System.Collections.ArrayList]::new()
foreach ($folder in $allFolders) {
    $path = Join-Path $PWD "$baseDir\$folder"
    if (Test-Path $path) {
        $files = Get-ChildItem $path -Filter "*Test.php" | Sort-Object Name
        foreach ($f in $files) {
            [void]$allFiles.Add(@{
                folder = $folder
                path   = $f.FullName
                name   = $f.Name
            })
        }
    }
}

if ($allFiles.Count -eq 0) {
    Write-Host "  No test files found!" -ForegroundColor Red
    exit 1
}

# Limit workers to file count
$workerCount = [Math]::Min($MaxWorkers, $allFiles.Count)
Write-Host "  Test files: $($allFiles.Count) → $workerCount workers" -ForegroundColor Gray

# Create output dir
$hotExists = Test-Path public/hot
if ($hotExists) { Move-Item public/hot public/hot.bak -Force }

New-Item -ItemType Directory -Force $outDir | Out-Null
Remove-Item "$outDir\*.log" -Force -ErrorAction SilentlyContinue
Remove-Item "$outDir\*.xml" -Force -ErrorAction SilentlyContinue

# Distribute files across workers (round-robin)
$buckets = New-Object 'System.Collections.Generic.List[object]'
for ($i = 0; $i -lt $workerCount; $i++) {
    [void]$buckets.Add((New-Object 'System.Collections.Generic.List[object]'))
}
for ($i = 0; $i -lt $allFiles.Count; $i++) {
    [void]$buckets[$i % $workerCount].Add($allFiles[$i])
}

# Launch workers
$jobs = @()
$start = Get-Date
$phpunitConfig = Join-Path $PWD "phpunit.dusk.xml"

for ($i = 0; $i -lt $workerCount; $i++) {
    $bucket = $buckets[$i]
    if ($bucket.Count -eq 0) { continue }

    $logAbs = Join-Path $PWD "$outDir\worker-$i.log"
    $junitAbs = Join-Path $PWD "$outDir\worker-$i.xml"

    # Build filter: use base class names only (no namespace backslash escaping issues)
    $classNames = ($bucket | ForEach-Object {
        [System.IO.Path]::GetFileNameWithoutExtension($_.name)
    }) -join "|"

    $job = Start-Job -Name "Dusk-W$i" -ScriptBlock {
        param($filter, $log, $junit, $wd)
        Set-Location $wd
        $cmd = "`$env:DUSK_ENABLED='true'; php artisan dusk --filter=`"$filter`" --log-junit=`"$junit`" 2>&1"
        Invoke-Expression $cmd | Out-File $log -Encoding utf8
    } -ArgumentList $classNames, $logAbs, $junitAbs, $PWD

    $jobs += $job
    $fileNames = ($bucket | ForEach-Object { $_.name }) -join ", "
    Write-Host "  Worker $i : $($bucket.Count) files → $logAbs" -ForegroundColor Yellow
}

# Monitor progress
Write-Host "`n=== Monitoring ===" -ForegroundColor Cyan
$spinner = @('|', '/', '-', '\')
$spinIdx = 0

while ($jobs | Where-Object { $_.State -eq 'Running' }) {
    $el = [math]::Round(((Get-Date) - $start).TotalSeconds, 0)
    $spin = $spinner[$spinIdx % 4]
    $spinIdx++

    $line = "$spin ${el}s | "
    for ($i = 0; $i -lt $workerCount; $i++) {
        $j = $jobs | Where-Object { $_.Name -eq "Dusk-W$i" }
        if (-not $j) { continue }
        $state = if ($j.State -eq 'Completed') { "OK" } elseif ($j.State -eq 'Failed') { "ERR" } elseif ($j.State -eq 'Running') { "RUN" } else { $j.State }

        $logAbs = Join-Path $PWD "$outDir\worker-$i.log"
        $size = if (Test-Path $logAbs) { "{0,4}KB" -f [math]::Round((Get-Item $logAbs).Length / 1KB, 0) } else { " 0KB" }

        $pass = 0; $fail = 0
        if (Test-Path $logAbs) {
            try { $txt = Get-Content $logAbs -Raw 2>$null; $pass = ([regex]::Matches($txt, [char]0x2713)).Count; $fail = ([regex]::Matches($txt, [char]0x2A2F)).Count } catch {}
        }

        $line += "[W$i $state $size +$pass/-$fail] "
    }

    Write-Host "`r$line" -NoNewline
    Start-Sleep -Seconds 1
}

$jobs | Wait-Job | Out-Null
Write-Host ""
$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)

if ($hotExists) { Move-Item public/hot.bak public/hot -Force }

# ============================================================
# PARSE JUNIT XML → STRUCTURED RESULTS
# ============================================================
Write-Host "`n=== Parsing Results ===" -ForegroundColor Cyan
$results = [System.Collections.ArrayList]::new()

for ($i = 0; $i -lt $workerCount; $i++) {
    $junitAbs = Join-Path $PWD "$outDir\worker-$i.xml"

    if (-not (Test-Path $junitAbs)) {
        Write-Host ("  [WARN] worker-" + $i + ": xml not found") -ForegroundColor DarkYellow
        continue
    }

    try {
        [xml]$junit = Get-Content $junitAbs -Encoding utf8

        $allSuites = @($junit.testsuites.testsuite)
        while ($allSuites.Count -gt 0 -and $allSuites[0].testcase -eq $null -and $allSuites[0].testsuite -ne $null) {
            $allSuites = @($allSuites[0].testsuite)
        }

        foreach ($suite in $allSuites) {
            if ($suite.testcase -eq $null) { continue }

            $className = $suite.name
            $classFile = $suite.file

            # Determine jenis_web from class namespace
            $jenisWeb = "unknown"
            foreach ($f in $jenisWebMap.Keys) {
                if ($className -match "Tests\\Browser\\Feature\\$f\\") {
                    $jenisWeb = $jenisWebMap[$f]
                    break
                }
            }

            @($suite.testcase) | ForEach-Object {
                $tc = $_
                $methodName = $tc.name
                $assertions = [int]$tc.assertions

                $status = "passed"
                $description = ""

                if ($tc.error -ne $null) {
                    $status = "failed"
                    $errText = $tc.error.'#text'
                    $errLines = $errText -split "[\r\n]+"
                    $msgLine = ""
                    foreach ($ln in $errLines) {
                        $t = $ln.Trim()
                        if ($t -match 'Exception:|Error:') { $msgLine = $t; break }
                        if ($t.Length -gt 10 -and $t -notmatch '^Tests\\' -and $t -notmatch '^C:\\\\') { $msgLine = $t; break }
                    }
                    if ($msgLine -eq "") { $msgLine = $errLines[0].Trim() }

                    if ($msgLine -match 'Missing required parameter.*dusk\.login') {
                        $status = "error"
                        $description = "Dusk auth route error - check dusk login route"
                    } else {
                        if ($msgLine.Length -gt 250) { $msgLine = $msgLine.Substring(0, 247) + "..." }
                        $description = $msgLine
                    }
                } elseif ($tc.failure -ne $null) {
                    $status = "failed"
                    $failText = $tc.failure.'#text'
                    $failLines = $failText -split "[\r\n]+"
                    $msgLine = ""
                    foreach ($ln in $failLines) {
                        $t = $ln.Trim()
                        if ($t -match 'Expected |Failed asserting|assertSee|assertDontSee|assertPresent') { $msgLine = $t; break }
                        if ($t.Length -gt 10 -and $t -notmatch '^Tests\\') { $msgLine = $t; break }
                    }
                    if ($msgLine -eq "") { $msgLine = $failLines[0].Trim() }
                    if ($msgLine.Length -gt 250) { $msgLine = $msgLine.Substring(0, 247) + "..." }
                    $description = $msgLine
                } elseif ($assertions -eq 0) {
                    $description = "no assertions performed"
                }

                [void]$results.Add(@{
                    jenis_web    = $jenisWeb
                    lokasi_file  = $classFile
                    method_test  = $methodName
                    assertion    = $assertions
                    status       = $status
                    description  = $description
                })
            }
        }
    } catch {
        Write-Host ("  [ERR] worker-$i xml: " + $_.Exception.Message) -ForegroundColor Red
    }
}

# ============================================================
# GENERATE CSV
# ============================================================
$csvPath = Join-Path $PWD $csvFile
$csvLines = [System.Collections.ArrayList]::new()

[void]$csvLines.Add('jenis web,lokasi file test case,method test case,total assertion,status,description')

foreach ($r in $results) {
    $web   = $r.jenis_web
    $file  = $r.lokasi_file
    $mtd   = $r.method_test
    $ass   = $r.assertion
    $stat  = $r.status
    $desc  = if ($r.description) { '"' + $r.description.Replace('"', '""') + '"' } else { "" }
    [void]$csvLines.Add("$web,$file,$mtd,$ass,$stat,$desc")
}

# Summary rows
[void]$csvLines.Add("")

$grandPassed = 0
$grandFailed = 0
$grandAssert = 0
$grandTotal = 0

foreach ($folder in $allFolders) {
    $jw = $jenisWebMap[$folder]
    $group = @($results | Where-Object { $_.jenis_web -eq $jw })
    if ($group.Count -eq 0) { continue }

    $p = ($group | Where-Object { $_.status -eq "passed" }).Count
    $f = ($group | Where-Object { $_.status -ne "passed" }).Count
    $a = if ($group.Count -gt 0) { ($group | Measure-Object -Property assertion -Sum -ErrorAction SilentlyContinue).Sum } else { 0 }
    $t = $group.Count

    [void]$csvLines.Add("$jw,,SUBTOTAL,,,")
    [void]$csvLines.Add("$jw,,> passed,$p,,")
    [void]$csvLines.Add("$jw,,> failed,$f,,")
    [void]$csvLines.Add("$jw,,> assertions,$a,,")
    [void]$csvLines.Add("$jw,,> total methods,$t,,")
    [void]$csvLines.Add("")

    $grandPassed += $p
    $grandFailed += $f
    $grandAssert += $a
    $grandTotal += $t
}

[void]$csvLines.Add("GRAND TOTAL,,SUBTOTAL,,,")
[void]$csvLines.Add("GRAND TOTAL,,> passed,$grandPassed,,")
[void]$csvLines.Add("GRAND TOTAL,,> failed,$grandFailed,,")
[void]$csvLines.Add("GRAND TOTAL,,> assertions,$grandAssert,,")
[void]$csvLines.Add("GRAND TOTAL,,> total methods,$grandTotal,,")

[System.IO.File]::WriteAllLines($csvPath, $csvLines, [System.Text.UTF8Encoding]::new($true))

# ============================================================
# CONSOLE SUMMARY
# ============================================================
Write-Host "`n=== Summary ($elapsed s) ===" -ForegroundColor Cyan

foreach ($folder in $allFolders) {
    $jw = $jenisWebMap[$folder]
    $p = ($results | Where-Object { $_.jenis_web -eq $jw -and $_.status -eq "passed" }).Count
    $f = ($results | Where-Object { $_.jenis_web -eq $jw -and $_.status -ne "passed" }).Count
    $a = ($results | Where-Object { $_.jenis_web -eq $jw } | Measure-Object -Property assertion -Sum -ErrorAction SilentlyContinue).Sum
    $st = if ($f -eq 0) { "PASS" } else { "FAIL" }
    $cl = if ($f -eq 0) { "Green" } else { "Red" }
    Write-Host "  $folder : $st | $p passed, $f failed | $a assertions" -ForegroundColor $cl
}

$totalP = ($results | Where-Object { $_.status -eq "passed" }).Count
$totalF = ($results | Where-Object { $_.status -ne "passed" }).Count
$totalA = if ($results.Count -gt 0) { ($results | Measure-Object -Property assertion -Sum -ErrorAction SilentlyContinue).Sum } else { 0 }

$fc = if ($totalF -eq 0) { "Green" } else { "Red" }
Write-Host "`nTotal: $totalP passed, $totalF failed, $totalA assertions | $elapsed s" -ForegroundColor $fc

$jobs | Remove-Job -Force
Write-Host "`nReport: $csvPath" -ForegroundColor Cyan
Write-Host "  Workers: $workerCount | Test files: $($allFiles.Count) | Buka CSV di Excel" -ForegroundColor Gray

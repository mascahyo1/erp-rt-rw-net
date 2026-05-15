# parallel-dusk.ps1
# Usage: .\parallel-dusk.ps1
# Runs Dusk tests in parallel + generates timestamped dusk-report CSV.
# Seed DB first: php artisan setup --demo

$ErrorActionPreference = "Stop"

$folders = @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")
$outDir = "tests\Browser\dusk-output"

$jenisWebMap = @{
    "OperatorSaas"        = "web operator saas"
    "OperatorPerusahaan"  = "web operator perusahaan"
    "Karyawan"            = "web karyawan"
    "Pelanggan"           = "web pelanggan"
}

$timestamp = (Get-Date).ToString("yyyyMMdd-HHmmss")
$csvFile = "$outDir\dusk-report-$timestamp.csv"

Write-Host "=== Parallel Dusk ($($folders.Count) workers) ===" -ForegroundColor Cyan

$hotExists = Test-Path public/hot
if ($hotExists) { Move-Item public/hot public/hot.bak -Force }

New-Item -ItemType Directory -Force $outDir | Out-Null
Remove-Item "$outDir\*.log" -Force -ErrorAction SilentlyContinue
Remove-Item "$outDir\*.xml" -Force -ErrorAction SilentlyContinue

$jobs = @()
$start = Get-Date

foreach ($folder in $folders) {
    $logFile = "$outDir\$folder.log"
    $junitFile = "$outDir\$folder.xml"

    $logAbs = Join-Path $PWD $logFile
    $junitAbs = Join-Path $PWD $junitFile

    $job = Start-Job -Name "Dusk-$folder" -ScriptBlock {
        param($f, $log, $junit, $wd)
        Set-Location $wd
        $cmd = "`$env:DUSK_ENABLED='true'; php artisan dusk --filter=`"$f`" --log-junit=`"$junit`" 2>&1"
        Invoke-Expression $cmd | Out-File $log -Encoding utf8
    } -ArgumentList $folder, $logAbs, $junitAbs, $PWD
    $jobs += $job
    Write-Host "  Worker $folder -> $logFile" -ForegroundColor Yellow
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
    foreach ($folder in $folders) {
        $j = $jobs | Where-Object { $_.Name -eq "Dusk-$folder" }
        $state = if ($j.State -eq 'Completed') { "OK" } elseif ($j.State -eq 'Failed') { "ERR" } elseif ($j.State -eq 'Running') { "RUN" } else { $j.State }

        $logAbs = Join-Path $PWD "$outDir\$folder.log"
        $size = if (Test-Path $logAbs) { "{0,5}KB" -f [math]::Round((Get-Item $logAbs).Length / 1KB, 0) } else { " 0KB" }

        $pass = 0; $fail = 0
        if (Test-Path $logAbs) {
            try { $txt = Get-Content $logAbs -Raw 2>$null; $pass = ([regex]::Matches($txt, [char]0x2713)).Count; $fail = ([regex]::Matches($txt, [char]0x2A2F)).Count } catch {}
        }

        $line += "[" + $folder + " " + $state + " " + $size + " +$pass/-$fail] "
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

foreach ($folder in $folders) {
    $junitAbs = Join-Path $PWD "$outDir\$folder.xml"
    $jenisWeb = $jenisWebMap[$folder]

    if (-not (Test-Path $junitAbs)) {
        Write-Host ("  [WARN] " + $folder + ": xml not found") -ForegroundColor DarkYellow
        continue
    }

    try {
        [xml]$junit = Get-Content $junitAbs -Encoding utf8

        # JUnit nested: testsuites > testsuite(phpunit.xml) > testsuite(Browser) > testsuite(ClassName...)
        # Walk recursively to find testcase elements
        $allSuites = @($junit.testsuites.testsuite)
        while ($allSuites.Count -gt 0 -and $allSuites[0].testcase -eq $null -and $allSuites[0].testsuite -ne $null) {
            $allSuites = @($allSuites[0].testsuite)
        }

        foreach ($suite in $allSuites) {
            if ($suite.testcase -eq $null) { continue }

            $className = $suite.name
            $classFile = $suite.file

            @($suite.testcase) | ForEach-Object {
                $tc = $_
                $methodName = $tc.name
                $assertions = [int]$tc.assertions
                $time = [math]::Round([float]$tc.time, 2)

                $status = "passed"
                $description = ""

                if ($tc.error -ne $null) {
                    $status = "failed"
                    $errMsg = $tc.error.'#text'
                    if ($errMsg -match 'Missing required parameter.*dusk\.login') {
                        $status = "error"
                        $description = "Dusk auth route error - check dusk login route"
                    } else {
                        $firstLine = ($errMsg -split "`n|`r")[0].Trim()
                        if ($firstLine.Length -gt 250) { $firstLine = $firstLine.Substring(0, 247) + "..." }
                        $description = $firstLine
                    }
                } elseif ($tc.failure -ne $null) {
                    $status = "failed"
                    $failMsg = $tc.failure.'#text'
                    $firstLine = ($failMsg -split "`n|`r")[0].Trim()
                    if ($firstLine.Length -gt 250) { $firstLine = $firstLine.Substring(0, 247) + "..." }
                    $description = $firstLine
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
        Write-Host ("  [ERR] Failed to parse " + $folder + ".xml: " + $_.Exception.Message) -ForegroundColor Red
    }
}

# ============================================================
# GENERATE CSV (Excel-compatible, UTF-8 BOM)
# ============================================================
$csvPath = Join-Path $PWD $csvFile
$csvLines = [System.Collections.ArrayList]::new()

# CSV header
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

[System.IO.File]::WriteAllLines($csvPath, $csvLines, [System.Text.UTF8Encoding]::new($true))

# ============================================================
# PRINT SUMMARY
# ============================================================
Write-Host "`n=== Summary ($elapsed s) ===" -ForegroundColor Cyan
$totalPassed = 0
$totalFailed = 0

foreach ($folder in $folders) {
    $pass = ($results | Where-Object { $_.jenis_web -eq $jenisWebMap[$folder] -and $_.status -eq "passed" }).Count
    $fail = ($results | Where-Object { $_.jenis_web -eq $jenisWebMap[$folder] -and $_.status -ne "passed" }).Count
    $status = if ($fail -eq 0) { "PASS" } else { "FAIL" }
    $color = if ($fail -eq 0) { "Green" } else { "Red" }
    Write-Host "  $folder : $status ($pass passed, $fail failed)" -ForegroundColor $color
    $totalPassed += $pass
    $totalFailed += $fail
}
$jobs | Remove-Job -Force

$finalColor = if ($totalFailed -eq 0) { "Green" } else { "Red" }
Write-Host "`nTotal: $totalPassed passed, $totalFailed failed | Time: ${elapsed}s" -ForegroundColor $finalColor

Write-Host "`nReport: $csvPath" -ForegroundColor Cyan
Write-Host "  Buka di Excel: Data → From Text/CSV → pilih file di atas (UTF-8)" -ForegroundColor Gray

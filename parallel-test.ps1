# parallel-test.ps1
# Usage: .\parallel-test.ps1 [-MaxWorkers 8]
# Runs php artisan test --testsuite=Feature in parallel (file-level) + generates timestamped CSV.

[CmdletBinding()]
param(
    [int]$MaxWorkers = 0
)

$ErrorActionPreference = "Stop"

$outDir = "tests\Browser\dusk-output"
$testDir = "tests\Feature"

$folders = @("Auth", "OperatorSaas", "OperatorPerusahaan")

if ($MaxWorkers -le 0) {
    Write-Host "=== Parallel Test (interactive) ===" -ForegroundColor Cyan
    $input = Read-Host "Jumlah worker paralel [default 4]"
    if ($input -match '^\d+$') { $MaxWorkers = [int]$input }
    if ($MaxWorkers -lt 1) { $MaxWorkers = 4 }
} else {
    Write-Host "=== Parallel Test ===" -ForegroundColor Cyan
}

# Discover test files
$allFiles = [System.Collections.ArrayList]::new()
foreach ($fFolder in $folders) {
    $path = Join-Path $PWD "$testDir\$fFolder"
    if (Test-Path $path) {
        Get-ChildItem $path -Recurse -Filter "*Test.php" | ForEach-Object {
            [void]$allFiles.Add(@{
                folder = $fFolder
                path   = $_.FullName
                name   = $_.Name
            })
        }
    }
}

if ($allFiles.Count -eq 0) {
    Write-Host "  No test files found!" -ForegroundColor Red
    exit 1
}

$workerCount = [Math]::Min($MaxWorkers, $allFiles.Count)
Write-Host "  Test files: $($allFiles.Count) -> $workerCount workers" -ForegroundColor Gray

New-Item -ItemType Directory -Force $outDir | Out-Null
Remove-Item "$outDir\ftest-*.log" -Force -ErrorAction SilentlyContinue
Remove-Item "$outDir\ftest-*.xml" -Force -ErrorAction SilentlyContinue

# Round-robin distribution
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
$timestamp = (Get-Date).ToString("yyyyMMdd-HHmmss")
$csvPath = Join-Path $PWD "$outDir\ftest-report-$timestamp.csv"

for ($i = 0; $i -lt $workerCount; $i++) {
    $bucket = $buckets[$i]
    if ($bucket.Count -eq 0) { continue }

    $logAbs = Join-Path $PWD "$outDir\ftest-w$i.log"
    $junitAbs = Join-Path $PWD "$outDir\ftest-w$i.xml"
    $fileList = ($bucket | ForEach-Object { '"' + $_.path + '"' }) -join " "

    $job = Start-Job -Name "Test-W$i" -ScriptBlock {
        param($files, $log, $junit, $wd)
        Set-Location $wd
        $cmd = "php artisan test $files --log-junit=`"$junit`" 2>&1"
        Invoke-Expression $cmd | Out-File $log -Encoding utf8
    } -ArgumentList $fileList, $logAbs, $junitAbs, $PWD

    $jobs += $job
    Write-Host "  Worker $i : $($bucket.Count) files" -ForegroundColor Yellow
}

# Monitor
Write-Host "`n=== Monitoring ===" -ForegroundColor Cyan
$spinner = @('|', '/', '-', '\')
$spinIdx = 0

while ($jobs | Where-Object { $_.State -eq 'Running' }) {
    $el = [math]::Round(((Get-Date) - $start).TotalSeconds, 0)
    $spin = $spinner[$spinIdx % 4]
    $spinIdx++

    $line = "$spin ${el}s | "
    for ($i = 0; $i -lt $workerCount; $i++) {
        $j = $jobs | Where-Object { $_.Name -eq "Test-W$i" }
        if (-not $j) { continue }
        $state = if ($j.State -eq 'Completed') { "OK" } elseif ($j.State -eq 'Failed') { "ERR" } elseif ($j.State -eq 'Running') { "RUN" } else { $j.State }
        $logAbs = Join-Path $PWD "$outDir\ftest-w$i.log"
        $size = if (Test-Path $logAbs) { "{0,4}KB" -f [math]::Round((Get-Item $logAbs).Length / 1KB, 0) } else { " 0KB" }
        $line += "[W$i $state $size] "
    }
    Write-Host "`r$line" -NoNewline
    Start-Sleep -Seconds 1
}

$jobs | Wait-Job | Out-Null
Write-Host ""
$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)

# Parse JUnit XML
$results = [System.Collections.ArrayList]::new()
for ($i = 0; $i -lt $workerCount; $i++) {
    $junitAbs = Join-Path $PWD "$outDir\ftest-w$i.xml"
    if (-not (Test-Path $junitAbs)) { continue }

    try {
        [xml]$junit = Get-Content $junitAbs -Encoding utf8
        $allSuites = @($junit.testsuites.testsuite)
        while ($allSuites.Count -gt 0 -and $allSuites[0].testcase -eq $null -and $allSuites[0].testsuite -ne $null) {
            $allSuites = @($allSuites[0].testsuite)
        }

        foreach ($suite in $allSuites) {
            if ($suite.testcase -eq $null) { continue }
            $classFile = $suite.file
            $className = $suite.name

            # Determine jenis test
            $jenis = "feature test"
            if ($className -match "Auth") { $jenis = "auth test" }
            elseif ($className -match "OperatorSaas") { $jenis = "operator saas" }
            elseif ($className -match "OperatorPerusahaan") { $jenis = "operator perusahaan" }

            @($suite.testcase) | ForEach-Object {
                $tc = $_
                $status = "passed"
                $desc = ""
                if ($tc.error -ne $null) {
                    $status = "failed"
                    $errLines = $tc.error.'#text' -split "[\r\n]+"
                    $desc = ($errLines | Where-Object { $_ -match 'Exception|Error|Failed' } | Select-Object -First 1).Trim()
                    if (-not $desc) { $desc = $errLines[0].Trim() }
                    if ($desc.Length -gt 200) { $desc = $desc.Substring(0, 197) + "..." }
                } elseif ($tc.failure -ne $null) {
                    $status = "failed"
                    $failLines = $tc.failure.'#text' -split "[\r\n]+"
                    $desc = ($failLines | Where-Object { $_ -match 'Expected|Failed|assert' } | Select-Object -First 1).Trim()
                    if (-not $desc) { $desc = $failLines[0].Trim() }
                    if ($desc.Length -gt 200) { $desc = $desc.Substring(0, 197) + "..." }
                }

                [void]$results.Add(@{
                    jenis      = $jenis
                    file       = $classFile
                    method     = $tc.name
                    assertions = [int]$tc.assertions
                    status     = $status
                    desc       = $desc
                })
            }
        }
    } catch {}
}

# CSV output
$csvLines = [System.Collections.ArrayList]::new()
[void]$csvLines.Add('jenis test,lokasi file,method test,assertions,status,description')

foreach ($r in $results) {
    $desc = if ($r.desc) { '"' + $r.desc.Replace('"', '""') + '"' } else { "" }
    [void]$csvLines.Add("$($r.jenis),$($r.file),$($r.method),$($r.assertions),$($r.status),$desc")
}

# Summary
[void]$csvLines.Add("")
foreach ($j in @("auth test", "operator saas", "operator perusahaan")) {
    $g = @($results | Where-Object { $_.jenis -eq $j })
    if ($g.Count -eq 0) { continue }
    $p = ($g | Where-Object { $_.status -eq "passed" }).Count
    $f = ($g | Where-Object { $_.status -ne "passed" }).Count
    $a = if ($g.Count -gt 0) { ($g | Measure-Object -Property assertions -Sum -ErrorAction SilentlyContinue).Sum } else { 0 }
    [void]$csvLines.Add("$j,,SUBTOTAL,pass=$p fail=$f assertions=$a,,")
}

$tp = ($results | Where-Object { $_.status -eq "passed" }).Count
$tf = ($results | Where-Object { $_.status -ne "passed" }).Count
$ta = if ($results.Count -gt 0) { ($results | Measure-Object -Property assertions -Sum -ErrorAction SilentlyContinue).Sum } else { 0 }
[void]$csvLines.Add("GRAND TOTAL,,pass=$tp fail=$tf assertions=$ta,,")

[System.IO.File]::WriteAllLines($csvPath, $csvLines, [System.Text.UTF8Encoding]::new($true))

# Console summary
Write-Host "`n=== Summary ($elapsed s) ===" -ForegroundColor Cyan
Write-Host "  Passed: $tp | Failed: $tf | Assertions: $ta" -ForegroundColor $(if ($tf -eq 0) { "Green" } else { "Red" })
Write-Host "`nReport: $csvPath" -ForegroundColor Cyan

$jobs | Remove-Job -Force

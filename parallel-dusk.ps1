# parallel-dusk.ps1
# Usage: .\parallel-dusk.ps1
# Runs Dusk tests in parallel + generates dusk-report.csv Excel output.
# Seed DB first: php artisan setup --demo

$ErrorActionPreference = "Stop"

$folders = @("OperatorSaas", "OperatorPerusahaan", "Karyawan", "Pelanggan")
$outDir = "tests\Browser\dusk-output"

# Map folder -> jenis web (nama kolom excel)
$jenisWebMap = @{
    "OperatorSaas"        = "web operator saas"
    "OperatorPerusahaan"  = "web operator perusahaan"
    "Karyawan"            = "web karyawan"
    "Pelanggan"           = "web pelanggan"
}

Write-Host "=== Parallel Dusk ($($folders.Count) workers) ===" -ForegroundColor Cyan

$hotExists = Test-Path public/hot
if ($hotExists) { Move-Item public/hot public/hot.bak -Force }

New-Item -ItemType Directory -Force $outDir | Out-Null
Remove-Item "$outDir\*.log" -Force -ErrorAction SilentlyContinue
Remove-Item "$outDir\*.xml" -Force -ErrorAction SilentlyContinue
Remove-Item "$outDir\dusk-report.csv" -Force -ErrorAction SilentlyContinue

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

$jobs | Wait-Job | Out-Null
$elapsed = [math]::Round(((Get-Date) - $start).TotalSeconds, 1)

if ($hotExists) { Move-Item public/hot.bak public/hot -Force }

# ============================================================
# PARSE LOGS → STRUCTURED RESULTS
# ============================================================
$results = [System.Collections.ArrayList]::new()

foreach ($folder in $folders) {
    $logFile = "$outDir\$folder.log"
    $junitFile = "$outDir\$folder.xml"
    $jenisWeb = $jenisWebMap[$folder]

    $logAbs = Join-Path $PWD $logFile
    $junitAbs = Join-Path $PWD $junitFile

    if (-not (Test-Path $logAbs)) {
        Write-Host ("  [WARN] " + $folder + ": log not found") -ForegroundColor DarkYellow
        continue
    }

    $lines = Get-Content $logAbs -Encoding utf8
    $currentClass = ""
    $classFile = ""
    $i = 0

    # Build map of test → assertions from JUnit XML if available
    $assertMap = @{}
    if (Test-Path $junitAbs) {
        try {
            [xml]$junit = Get-Content $junitAbs -Encoding utf8
            $junit.testsuites.testsuite | ForEach-Object {
                $suite = $_
                $suiteFile = $suite.file
                if ($suite.'@name') {
                    # each <testcase> maps to a method
                    $suite.testcase | ForEach-Object {
                        $tc = $_
                        $fullName = "$($suite.'@name')::$($tc.'@name')"
                        $assertMap[$fullName] = [int]$tc.'@assertions'
                    }
                }
            }
        } catch {
            Write-Host "  [WARN] Cannot parse JUnit XML for $folder" -ForegroundColor DarkYellow
        }
    }

    for ($i = 0; $i -lt $lines.Count; $i++) {
        $line = $lines[$i]

        # Match PASS/FAIL + class name: "   PASS  Tests\Browser\Feature\Folder\ClassName"
        if ($line -match '^\s*(PASS|FAIL)\s+(Tests\\Browser\\Feature\\(.+?))\s*$') {
            $currentClass = $Matches[2]
            # Convert namespace to file path
            $classFile = $currentClass -replace '\\', '/' -replace '^Tests/', 'tests/' -replace '$', '.php'
            continue
        }

        # Match test method header lines like:
        # "  ✓ test_01_page_renders                                                                 0.32s"
        # "  ✓ test_02_search"
        # "  ⨯ test_filter_status                                                                     0.50s"
        # The checkmark/ballot-x may not have a space before it in all environments
        if ($line -match '^\s*✓\s+(test_\w+\S*)') {
            $method = $Matches[1]
            $key = "$currentClass`::$method"
            $assertions = if ($assertMap.ContainsKey($key)) { $assertMap[$key] } else { 0 }
            [void]$results.Add(@{
                jenis_web    = $jenisWeb
                lokasi_file  = $classFile
                method_test  = $method
                assertion    = $assertions
                status       = "passed"
                description  = ""
            })
            continue
        }

        if ($line -match '^\s*⨯\s+(test_\w+\S*)') {
            $method = $Matches[1]
            $key = "$currentClass`::$method"
            $assertions = if ($assertMap.ContainsKey($key)) { $assertMap[$key] } else { 0 }

            # Collect error description: lines until next "─" separator, "PASS/FAIL" header, or end
            $descLines = @()
            for ($j = $i + 1; $j -lt $lines.Count; $j++) {
                $nextLine = $lines[$j]
                if ($nextLine -match '─────────────────────────' -or
                    $nextLine -match '^\s*(PASS|FAIL)\s+Tests\\' -or
                    $nextLine -match '^\s*(✓|⨯)\s+' -or
                    $nextLine -match '^\s*Tests:' -or
                    $nextLine -match '^\s*OK\s*\(' -or
                    $nextLine -match '^\s*FAILURES!') {
                    break
                }
                $trimmed = $nextLine.Trim()
                if ($trimmed.Length -gt 0) {
                    $descLines += $trimmed
                }
            }
            # Build description: take the FAILED line and first meaningful error
            $description = ""
            foreach ($d in $descLines) {
                if ($d -match '^FAILED\s+') { continue }
                if ($d -match '^Expected\s+' -or $d -match 'Failed asserting' -or $d -match '^SQLSTATE') {
                    $description = $d -replace '"', '""'  # escape for CSV
                    if ($description.Length -gt 200) { $description = $description.Substring(0, 197) + "..." }
                    break
                }
            }
            if ($description -eq "" -and $descLines.Count -gt 0) {
                $description = $descLines[0] -replace '"', '""'
                if ($description.Length -gt 200) { $description = $description.Substring(0, 197) + "..." }
            }

            [void]$results.Add(@{
                jenis_web    = $jenisWeb
                lokasi_file  = $classFile
                method_test  = $method
                assertion    = $assertions
                status       = "failed"
                description  = $description
            })
            continue
        }
    }
}

# ============================================================
# GENERATE CSV (Excel-compatible)
# ============================================================
$csvFile = "$outDir\dusk-report.csv"
$csvLines = [System.Collections.ArrayList]::new()

# Header
[void]$csvLines.Add('jenis web,lokasi file test case,method test case,total assertion,status,description')

foreach ($r in $results) {
    $web   = $r.jenis_web
    $file  = $r.lokasi_file
    $mtd   = $r.method_test
    $ass   = $r.assertion
    $stat  = $r.status
    $desc  = if ($r.description -and $r.description.Length -gt 0) { "`"$($r.description)`"" } else { "" }

    [void]$csvLines.Add("$web,$file,$mtd,$ass,$stat,$desc")
}

[System.IO.File]::WriteAllLines((Join-Path $PWD $csvFile), $csvLines, [System.Text.UTF8Encoding]::new($true))

# ============================================================
# PRINT SUMMARY
# ============================================================
Write-Host "`n=== Summary ($elapsed s) ===" -ForegroundColor Cyan
$totalPassed = 0
$totalFailed = 0

foreach ($folder in $folders) {
    $logAbs = Join-Path $PWD "$outDir\$folder.log"
    if (Test-Path $logAbs) {
        $content = Get-Content $logAbs -Raw
        $pass = ([regex]::Matches($content, '✓')).Count
        $fail = ([regex]::Matches($content, '⨯')).Count
        $status = if ($fail -eq 0) { "PASS" } else { "FAIL" }
        $color = if ($fail -eq 0) { "Green" } else { "Red" }
        Write-Host "  $folder : $status ($pass passed, $fail failed) -> $logAbs" -ForegroundColor $color
        $totalPassed += $pass
        $totalFailed += $fail
    }
}
$jobs | Remove-Job -Force

$finalColor = if ($totalFailed -eq 0) { "Green" } else { "Red" }
Write-Host "`nTotal: $totalPassed passed, $totalFailed failed | Time: ${elapsed}s" -ForegroundColor $finalColor

# CSV info
$csvAbs = Join-Path $PWD $csvFile
Write-Host "`nReport saved: $csvAbs" -ForegroundColor Cyan
Write-Host "  Buka di Excel: Data → From Text/CSV → pilih file di atas" -ForegroundColor Gray

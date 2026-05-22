# Dokumentasi Running Test Dusk

## Persyaratan Sistem

### 1. Chrome Browser
Pastikan Google Chrome sudah terinstall:
```
Chrome version: 148.0.7778.179 atau compatible
```

### 2. ChromeDriver
ChromeDriver harus match dengan versi Chrome:

| Chrome Version | ChromeDriver Path |
|---------------|------------------|
| 148.0.7778.x | `vendor/laravel/dusk/bin/chromedriver-win.exe` (sudah tersedia) |
| 149.x.x.x | Download dari [Chrome for Testing](https://googlechromelabs.github.io/chrome-for-testing/) |
| 150.x.x.x (Dev) | Download dari [Chrome for Testing](https://googlechromelabs.github.io/chrome-for-testing/) |

Untuk update ChromeDriver manual:
```powershell
# Download dari https://googlechromelabs.github.io/chrome-for-testing/
Invoke-WebRequest -Uri "https://storage.googleapis.com/chrome-for-testing-public/VERSION/win32/chromedriver-win32.zip" -OutFile "c:\temp\chromedriver.zip"
Expand-Archive -Path "c:\temp\chromedriver.zip" -DestinationPath "c:\temp\chromedriver"
Copy-Item -Path "c:\temp\chromedriver\chromedriver-win32\chromedriver.exe" -Destination "vendor\laravel\dusk\bin\chromedriver-win.exe" -Force
```

### 3. FFmpeg (untuk Video Recording)
FFmpeg diperlukan untuk menghasilkan file video MP4 dari screenshot test.

Install via winget:
```powershell
winget install --id=Gyan.FFmpeg -e --source winget --accept-package-agreements --accept-source-agreements
```

FFmpeg akan di-detect otomatis dari lokasi:
- `C:\Users\user\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-8.1.1-full_build\bin\ffmpeg.exe`
- `C:\ffmpeg\bin\ffmpeg.exe`
- `C:\Program Files\ffmpeg\bin\ffmpeg.exe`
- PATH system (ffmpeg)

Jika FFmpeg tidak tersedia, video recording fallback ke HTML player.

---

## Cara Running Test

### 1. Single Test File
```powershell
php artisan dusk tests/Browser/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.php
```

### 2. Single Test Method
```powershell
php artisan dusk --filter="test_01_page_renders" tests/Browser/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.php
```

### 3. Multiple Test Files (Daftar Paket)
```powershell
php artisan dusk tests/Browser/Feature/OperatorPerusahaan/DaftarPaketViewTest.php tests/Browser/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.php tests/Browser/Feature/OperatorPerusahaan/DaftarPaketImportExportTest.php tests/Browser/Feature/OperatorPerusahaan/DaftarPaketPermissionTest.php
```

### 4. Use Parallel Script
```powershell
.\parallel-dusk.ps1
```

Ikuti prompts:
1. Pilih portal: 2 (OperatorPerusahaan)
2. Jumlah worker: 1 (untuk debugging) atau lebih tinggi untuk parallel
3. Test akan berjalan dan video recording akan di-generate

---

## Troubleshooting

### ChromeDriver tidak connect (port 9515)
Pastikan ChromeDriver berjalan di port yang benar:
```powershell
# Kill semua process terkait
taskkill /F /IM chromedriver-win.exe
taskkill /F /IM chrome.exe

# Start ChromeDriver
Start-Process "vendor\laravel\dusk\bin\chromedriver-win.exe" -ArgumentList "--port=9515" -WindowStyle Hidden

# Cek apakah sudah running
netstat -ano | Select-String "9515"
```

### Chrome crash / breakpoint error
Pastikan versi ChromeDriver match dengan Chrome:
```powershell
# Cek versi Chrome
chrome --version

# Update ChromeDriver
php artisan dusk:chrome-driver
```

### FFmpeg not found
Video akan fallback ke HTML player. Untuk install FFmpeg:
```powershell
winget install --id=Gyan.FFmpeg -e --source winget
```

---

## Output Video Recording

Video recording disimpan di:
```
tests/Browser/videos/
```

Format output:
- **MP4** (jika FFmpeg tersedia): `.mp4` file
- **HTML** (fallback): `.html` file dengan player interaktif

Nama file: `{TestName}_{YYYYMMDD_HHMMSS}.mp4` atau `.html`

---

## Configuration

File config: `config/dusk.php`
```php
<?php
return [
    'driver_url' => env('DUSK_DRIVER_URL', 'http://localhost:9515'),
    'chrome_extensions' => [],
];
```

Environment variable:
- `DUSK_DRIVER_URL`: Custom ChromeDriver URL
- `DUSK_HEADLESS_DISABLED=true`: Disable headless mode untuk debugging visual
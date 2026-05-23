# Playwright Tests - Node.js

## Why Playwright instead of Dusk?

| Feature | Playwright | Dusk |
|---------|-----------|------|
| Video Recording | Native, 60fps, stable | Custom, buggy, unstable |
| Stabilitas | ✅ Stable | ⚠️ Sering crash |
| API | Modern JS/Node.js | PHP |
| Browser Support | Chrome, Firefox, Safari | Chrome only |
| Setup | Mudah | Kompleks |
| Screenshot | Native support | Via Laravel Dusk |

## Struktur

```
tests/Browser/Playwright/
├── Feature/
│   ├── OperatorSaas/
│   │   ├── AdminPerusahaanCRUDTest.cjs
│   │   ├── AdminSaaSCRUDTest.cjs
│   │   ├── DashboardTest.cjs
│   │   ├── LoginTest.cjs
│   │   ├── PerusahaanCRUDTest.cjs
│   │   ├── RolePerusahaanCRUDTest.cjs
│   │   └── RoleSaaSCRUDTest.cjs
│   ├── OperatorPerusahaan/
│   │   ├── CustomerCRUDTest.cjs
│   │   ├── DaftarPaketCRUDTest.cjs
│   │   ├── DaftarPaketPermissionTest.cjs
│   │   ├── DaftarPaketViewTest.cjs
│   │   ├── DashboardTest.cjs
│   │   ├── KaryawanCRUDTest.cjs
│   │   ├── LanggananCustomerCRUDTest.cjs
│   │   └── LoginTest.cjs
│   ├── Karyawan/
│   │   ├── CustomerViewTest.cjs
│   │   ├── DashboardTest.cjs
│   │   ├── InsentifSayaViewTest.cjs
│   │   ├── LanggananCustomerViewTest.cjs
│   │   ├── LoginTest.cjs
│   │   ├── ProfilSayaViewTest.cjs
│   │   ├── RiwayatPembayaranViewTest.cjs
│   │   └── TagihanViewTest.cjs
│   └── Pelanggan/
│       ├── DashboardTest.cjs
│       ├── LoginTest.cjs
│       ├── PaketSayaViewTest.cjs
│       ├── ProfilSayaViewTest.cjs
│       └── TagihanSayaViewTest.cjs
├── result/                    # Screenshots output
│   ├── OperatorSaas/
│   ├── OperatorPerusahaan/
│   ├── Karyawan/
│   └── Pelanggan/
├── support/
│   └── PlaywrightHelper.cjs
├── runner.cjs
└── README.md
```

## Result Structure

Screenshot output mengikuti struktur:
```
tests/Browser/Playwright/result/{Portal}/{Fitur}/{TestName}/{step}.png
```

Contoh:
```
tests/Browser/Playwright/result/OperatorPerusahaan/DaftarPaket/TestCRUD/01-page.png
tests/Browser/Playwright/result/OperatorPerusahaan/DaftarPaket/TestPermission/01-list-blocked.png
tests/Browser/Playwright/result/Karyawan/Dashboard/00-login.png
```

## Install

```bash
npm install playwright
npx playwright install chromium
```

## Run Tests

### Login Tests (Tidak butuh credentials)
```bash
node tests/Browser/Playwright/Feature/OperatorSaas/LoginTest.cjs
node tests/Browser/Playwright/Feature/OperatorPerusahaan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Karyawan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Pelanggan/LoginTest.cjs
```

### CRUD Tests (Perlu credentials database)

Export credentials lalu jalankan:
```bash
# Windows PowerShell
$env:TEST_OP_EMAIL="admin@perusahaan.rtrwnet.id"
$env:TEST_OP_PASSWORD="your-password"
node tests/Browser/Playwright/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.cjs
```

### Semua Login Tests Sekaligus
```bash
node tests/Browser/Playwright/Feature/OperatorSaas/LoginTest.cjs
node tests/Browser/Playwright/Feature/OperatorPerusahaan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Karyawan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Pelanggan/LoginTest.cjs
```

## Hasil Test

- Screenshots tersimpan di `tests/Browser/Playwright/result/`
- Folder `result` sudah di-gitignore
- Struktur: `result/{Portal}/{Fitur}/{TestName}/{screenshot}.png`

## Troubleshooting

### "Cannot find module"
Pastikan file menggunakan `.cjs` extension, bukan `.js`

### Login failed - credentials do not match
CRUD tests butuh user yang ada di database. Buat user baru atau gunakan user yang sudah ada:
```bash
php artisan tinker
App\Models\AdminCompany::factory()->create(['email' => 'test@perusahaan.rtrwnet.id']);
```

### Page timeout
Tambah wait time:
```javascript
await this.helper.page.waitForTimeout(5000);
```

## Konversi Dusk ke Playwright

| Dusk (PHP) | Playwright (JS) |
|------------|-----------------|
| `$browser->visit('/url')` | `await page.goto(baseUrl + '/url')` |
| `$browser->waitForText('text')` | `await helper.waitForText('text')` |
| `$browser->assertSee('text')` | `const text = await helper.getText('body'); if (!text.includes('text')) throw` |
| `$browser->click('selector')` | `await helper.click('selector')` |
| `$browser->type('selector', 'val')` | `await helper.fill('selector', 'val')` |
| `$browser->screenshot('path')` | `await helper.screenshot('path')` |
| `$browser->pause(1000)` | `await helper.pause(1000)` |
| `$browser->loginAs($user, 'guard')` | `await helper.loginAs*(email, pass)` |

## Catatan Penting

1. **Jangan hapus file Dusk lama** - tetap simpan di `tests/Browser/deprecatedoldFeature/`
2. **Screenshots di-gitignore** - jangan di-commit
3. **Credentials jangan di-commit** - gunakan environment variables
4. **Test independence** - setiap test harus bisa jalan sendiri
5. **Hasil test** di folder `result/` mengikuti struktur: `result/{Portal}/{Fitur}/{TestName}/`
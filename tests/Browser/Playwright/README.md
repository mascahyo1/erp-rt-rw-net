# Playwright Tests - Node.js

## Struktur

```
tests/Browser/Playwright/
├── Feature/
│   ├── OperatorSaas/
│   │   ├── AdminSaaSCRUD.cjs
│   │   └── LoginTest.cjs
│   ├── OperatorPerusahaan/
│   │   ├── CustomerCRUD.cjs
│   │   ├── DaftarPaketCRUD.cjs
│   │   ├── LanggananCustomerCRUD.cjs
│   │   └── LoginTest.cjs
│   ├── Karyawan/
│   │   └── LoginTest.cjs
│   └── Pelanggan/
│       └── LoginTest.cjs
├── result/                    # Screenshots (di-gitignore)
├── support/
│   └── PlaywrightHelper.cjs
├── runner.cjs
└── README.md
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
# Linux/Mac
export TEST_OP_EMAIL="admin@perusahaan.rtrwnet.id"
export TEST_OP_PASSWORD="your-password"
node tests/Browser/Playwright/Feature/OperatorPerusahaan/DaftarPaketCRUD.cjs

# Windows PowerShell
$env:TEST_OP_EMAIL="admin@perusahaan.rtrwnet.id"
$env:TEST_OP_PASSWORD="your-password"
node tests/Browser/Playwright/Feature/OperatorPerusahaan/DaftarPaketCRUD.cjs

# Atau langsung passing args
node tests/Browser/Playwright/Feature/OperatorPerusahaan/DaftarPaketCRUD.cjs admin@perusahaan.rtrwnet.id password
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
# Buat user via tinker
php artisan tinker
App\Models\AdminCompany::factory()->create(['email' => 'test@perusahaan.rtrwnet.id']);
```

### Page timeout
Tambah wait time:
```javascript
await this.helper.page.waitForTimeout(5000);
```

## Contoh Penggunaan PlaywrightHelper

```javascript
const PlaywrightHelper = require('path/to/support/PlaywrightHelper.cjs');

const helper = new PlaywrightHelper();
await helper.launch();

// Login
await helper.loginAsAdminPerusahaan('email@domain.com', 'password');

// Navigasi
await helper.page.goto('http://erp-rt-rw-net.test/operator-perusahaan/daftar-paket');
await helper.page.waitForLoadState('networkidle');

// Screenshot
await helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/01-page');

// Klik
await helper.click('button:has-text("Tambah")');

// Form
await helper.fill('input[placeholder="Nama"]', 'Value');

// Verify
const text = await helper.getText('body');
if (text.includes('Success')) console.log('OK');

// Cleanup
await helper.close();
```

## Membuat Test Baru

1. Buat file `.cjs` di folder yang sesuai
2. Import PlaywrightHelper dengan path absolut:

```javascript
const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');
```

3. Class harus punya method `runAllTests(email, password)`
4. Di dalam `runAllTests`:
   - Panggil `await this.helper.launch()` di dalam try block
   - Screenshot di setiap step
   - Cleanup di finally block

```javascript
class MyTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.results = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests(email, password) {
        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan(email, password);
            await this.screenshot('00-login');
            // test cases...
        } finally {
            await this.helper.close();
        }
    }
}

const test = new MyTest();
const email = process.argv[2] || 'default@email.com';
const password = process.argv[3] || 'password';
test.runAllTests(email, password);
```

## Konversi Dusk ke Playwright

| Dusk (PHP) | Playwright (JS) |
|------------|-----------------|
| `$browser->visit('/url')` | `await page.goto(baseUrl + '/url')` |
| `$browser->waitForText('text')` | `await page.waitForFunction(...)` |
| `$browser->assertSee('text')` | `await page.textContent()` then assert |
| `$browser->click('selector')` | `await page.click('selector')` |
| `$browser->type('selector', 'val')` | `await page.fill('selector', 'val')` |
| `$browser->screenshot('path')` | `await page.screenshot({ path: 'path.png' })` |
| `$browser->pause(1000)` | `await page.waitForTimeout(1000)` |
| `$browser->loginAs($user, 'guard')` | `await helper.loginAs*(email, pass)` |

## Catatan Penting

1. **Jangan hapus file Dusk lama** - tetap simpan di `tests/Browser/deprecatedoldFeature/`
2. **Screenshots di-gitignore** - jangan di-commit
3. **Credentials jangan di-commit** - gunakan environment variables
4. **Test独立性** - setiap test harus bisa jalan sendiri
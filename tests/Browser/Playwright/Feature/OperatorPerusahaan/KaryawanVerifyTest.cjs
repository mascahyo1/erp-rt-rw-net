// Comprehensive verification test for Karyawan CRUD + Import/Export
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class KaryawanVerifyTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Karyawan', 'VerifyTest');
        this.screenshotCount = 0;
        this.downloadsDir = path.join(__dirname, '..', '..', '..', '..', 'storage', 'app', 'temp', 'verify-downloads');
        this.testResults = [];
    }

    async shot(page, name) {
        if (!fs.existsSync(this.screenshotDir)) fs.mkdirSync(this.screenshotDir, { recursive: true });
        this.screenshotCount++;
        const f = path.join(this.screenshotDir, `${String(this.screenshotCount).padStart(2,'0')}-${name}.png`);
        await page.screenshot({ path: f, fullPage: false });
        console.log(`  [SS] ${f}`);
        return f;
    }

    log(name, ok, note = '') {
        this.testResults.push({ name, ok, note });
        console.log(`  ${ok ? '✓' : '✗'} ${name}${note ? ' — ' + note : ''}`);
    }

    async run() {
        console.log('=== KARYAWAN VERIFY TEST (modal, datatable, import, export) ===\n');
        const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
        const context = await browser.newContext({ viewport: { width: 1366, height: 850 }, acceptDownloads: true });
        const page = await context.newPage();

        try {
            // 0) Clear downloads dir to avoid stale files
            if (fs.existsSync(this.downloadsDir)) {
                for (const f of fs.readdirSync(this.downloadsDir)) {
                    try { fs.unlinkSync(path.join(this.downloadsDir, f)); } catch {}
                }
            } else {
                fs.mkdirSync(this.downloadsDir, { recursive: true });
            }

            // 1) LOGIN with correct flow (click company dropdown button → search → select)
            console.log('\n[1] Login flow');
            await page.goto(`${BASE}/login-perusahaan`);
            await page.waitForLoadState('networkidle');
            await this.shot(page, 'login-page');

            // Click the company selector button (has fa-building icon)
            const companyBtn = page.locator('button:has(.fa-building)').first();
            await companyBtn.click();
            await page.waitForTimeout(800);
            await this.shot(page, 'login-company-dropdown-open');

            // Now the search input is visible — fill it
            const companySearch = page.locator('input[placeholder*="Cari perusahaan"]').first();
            await companySearch.fill('Digital Media');
            await page.waitForTimeout(1000);
            await this.shot(page, 'login-company-search');

            // Click on "CV Digital Media Nusantara" option
            const companyOption = page.locator('text=CV Digital Media Nusantara').first();
            await companyOption.click();
            await page.waitForTimeout(500);

            // Email & password
            await page.fill('input[type="email"]', 'admin@digitalmedia.id');
            await page.fill('input[type="password"]', 'password123');
            await this.shot(page, 'login-filled');

            await page.click('button[type="submit"]');
            await page.waitForTimeout(4000);
            await this.shot(page, 'login-after');

            const dashboardUrl = page.url();
            this.log('Login berhasil', !dashboardUrl.includes('login'), `URL: ${dashboardUrl}`);

            // 2) KARYAWAN PAGE
            console.log('\n[2] Halaman Karyawan (datatable)');
            await page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
            await this.shot(page, 'karyawan-page-list');

            // Check datatable exists
            const hasTable = await page.$('table') !== null;
            const hasTableRows = await page.$$eval('tbody tr', rows => rows.length);
            this.log('Datatable tampil', hasTable && hasTableRows > 0, `${hasTableRows} rows`);

            // Check Kode column visible in datatable
            const headers = await page.$$eval('thead th', ths => ths.map(t => t.innerText.trim()));
            this.log('Kolom Kode di header tabel', headers.includes('Kode'), `headers: ${headers.join(' | ')}`);

            // Check Import + Export buttons visible
            const importBtn = await page.$('button:has-text("Import")');
            const exportBtn = await page.$('button:has-text("Export")');
            const tambahBtn = await page.$('button:has-text("Tambah Karyawan")');
            const templateLink = await page.$('a:has-text("Template")');
            this.log('Tombol Tambah Karyawan terlihat', tambahBtn !== null);
            this.log('Tombol Import terlihat', importBtn !== null);
            this.log('Tombol Export terlihat', exportBtn !== null);
            this.log('Link Template terlihat', templateLink !== null);

            // 3) MODAL TAMBAH
            console.log('\n[3] Modal Tambah Karyawan');
            if (tambahBtn) {
                await tambahBtn.click();
                await page.waitForTimeout(1500);
                await this.shot(page, 'karyawan-modal-tambah');

                const modalText = await page.textContent('body');
                const hasTambah = modalText.includes('Tambah Karyawan');
                const hasNamaField = await page.$('input[placeholder="Nama lengkap"]') !== null;
                const hasEmailField = await page.$('input[type="email"]') !== null;
                const hasPasswordField = await page.$('input[type="password"]') !== null;
                const hasPhoneField = await page.$('input[placeholder="81234567890"]') !== null;
                const hasKodeField = await page.$('input[placeholder*="KRY001"]') !== null;
                this.log('Modal Tambah terbuka', hasTambah);
                this.log('Field Kode Karyawan ada', hasKodeField);
                this.log('Field Nama ada', hasNamaField);
                this.log('Field Email ada', hasEmailField);
                this.log('Field Password ada', hasPasswordField);
                this.log('Field Telepon ada', hasPhoneField);

                // Close modal
                const closeTambah = page.locator('button:has-text("Batal")').first();
                if (await closeTambah.count() > 0) await closeTambah.click();
                await page.waitForTimeout(500);
            } else {
                this.log('Tombol Tambah Karyawan terlihat', false);
            }

            // 4) MODAL EDIT
            console.log('\n[4] Modal Edit Karyawan');
            const editBtn = await page.$('button[title="Edit"]');
            if (editBtn) {
                await editBtn.click();
                await page.waitForTimeout(1500);
                await this.shot(page, 'karyawan-modal-edit');

                const modalText = await page.textContent('body');
                const hasEdit = modalText.includes('Edit Karyawan');
                const hasKodeFieldEdit = await page.$('input[placeholder*="KRY001"]') !== null;
                this.log('Modal Edit terbuka', hasEdit);
                this.log('Field Kode Karyawan (edit) ada', hasKodeFieldEdit);

                // Check Kode is pre-filled
                const kodeVal = await page.$eval('input[placeholder*="KRY001"]', el => el.value);
                this.log('Field Kode pre-filled dengan KRY00x', kodeVal.startsWith('KRY'), `value="${kodeVal}"`);

                // Close
                const closeEdit = page.locator('button:has-text("Batal")').first();
                if (await closeEdit.count() > 0) await closeEdit.click();
                await page.waitForTimeout(500);
            } else {
                this.log('Tombol Edit row ditemukan', false, 'tidak ada data karyawan atau tombol Edit tidak ada');
            }

            // 5) MODAL IMPORT
            console.log('\n[5] Modal Import Karyawan');
            if (importBtn) {
                await importBtn.click();
                await page.waitForTimeout(1500);
                await this.shot(page, 'karyawan-modal-import');

                const modalText = await page.textContent('body');
                const hasImport = modalText.includes('Import Karyawan');
                const hasFileInput = await page.$('input[type="file"][accept*="xlsx"]') !== null;
                const hasTemplateLinkInModal = modalText.includes('Download template');
                this.log('Modal Import terbuka', hasImport);
                this.log('Input file xlsx terlihat', hasFileInput);
                this.log('Info download template terlihat', hasTemplateLinkInModal);

                // Close
                const closeImport = page.locator('button:has-text("Batal")').first();
                if (await closeImport.count() > 0) await closeImport.click();
                await page.waitForTimeout(500);
            }

            // 6) DOWNLOAD TEMPLATE
            console.log('\n[6] Download Template Excel');
            if (templateLink) {
                if (!fs.existsSync(this.downloadsDir)) fs.mkdirSync(this.downloadsDir, { recursive: true });
                const [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
                    templateLink.click({ force: true })
                ]);
                if (download) {
                    const savePath = path.join(this.downloadsDir, download.suggestedFilename());
                    await download.saveAs(savePath);
                    const stat = fs.statSync(savePath);
                    this.log('Template Excel terdownload', stat.size > 0, `${download.suggestedFilename()} (${stat.size} bytes)`);
                } else {
                    this.log('Template Excel terdownload', false, 'tidak ada event download');
                }
            }

            // 7) EXPORT ALL
            console.log('\n[7] Export Semua ke Excel');
            if (exportBtn) {
                if (!fs.existsSync(this.downloadsDir)) fs.mkdirSync(this.downloadsDir, { recursive: true });
                const [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
                    exportBtn.click({ force: true })
                ]);
                if (download) {
                    const savePath = path.join(this.downloadsDir, download.suggestedFilename());
                    await download.saveAs(savePath);
                    const stat = fs.statSync(savePath);
                    this.log('Export Excel terdownload', stat.size > 0, `${download.suggestedFilename()} (${stat.size} bytes)`);
                } else {
                    this.log('Export Excel terdownload', false, 'tidak ada event download');
                }
            }

            // 8) VERIFY EXPORT FILE CONTENT (header + size; use unzip to peek)
            console.log('\n[8] Verifikasi isi file Excel');
            const xlsxFiles = fs.readdirSync(this.downloadsDir).filter(f => f.endsWith('.xlsx'));
            if (xlsxFiles.length > 0) {
                const xlsxPath = path.join(this.downloadsDir, xlsxFiles[0]);
                const stat = fs.statSync(xlsxPath);
                const { execSync } = require('child_process');
                try {
                    const xml = execSync(`powershell -NoProfile -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; $z = [System.IO.Compression.ZipFile]::OpenRead('${xlsxPath.replace(/\\/g, '\\\\')}'); $entry = $z.Entries | Where-Object { $_.FullName -eq 'xl/sharedStrings.xml' }; $reader = New-Object System.IO.StreamReader($entry.Open()); $reader.ReadToEnd(); $z.Dispose()"`, { encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
                    const names = ['Ahmad Fauzi','Siti Nuraini','Budi Santoso','Dewi Lestari','Rudi Hermawan','Hendra Gunawan','Ratna Sari','Andi Prasetyo','Maya Indah','Imported'];
                    const found = names.filter(n => xml.includes(n));
                    this.log('Isi file Export Excel valid', found.length > 0, `${found.length} nama karyawan dikenal di sharedStrings: ${found.slice(0,5).join(', ')}`);

                    // Verify Kode Karyawan column header exists
                    const hasKodeHeader = xml.includes('Kode');
                    this.log('Kolom Kode di header Excel', hasKodeHeader);

                    // Verify KRY00x codes present
                    const codes = ['KRY001','KRY002','KRY003','KRY004','KRY005','KRY006','KRY007','KRY008','KRY009','KRY100','IMP001','IMP002','DUPCODE','KRYSHARED'];
                    const foundCodes = codes.filter(c => xml.includes(c));
                    this.log('Kode Karyawan (KRY00x) muncul di Excel', foundCodes.length > 0, `found: ${foundCodes.join(', ')}`);

                    console.log(`  File: ${xlsxFiles[0]} (${stat.size} bytes)`);
                    console.log(`  Detected names: ${found.join(', ')}`);
                    console.log(`  Detected codes: ${foundCodes.join(', ')}`);
                } catch (e) {
                    this.log('Isi file Export Excel valid', false, e.message.substring(0, 100));
                }
            }

        } catch (e) {
            console.log(`\n[FATAL] ${e.message}`);
            await this.shot(page, 'fatal-error');
        } finally {
            await browser.close();
        }

        // Summary
        console.log('\n=== SUMMARY ===');
        const passed = this.testResults.filter(r => r.ok).length;
        const failed = this.testResults.filter(r => !r.ok).length;
        console.log(`Passed: ${passed} | Failed: ${failed}`);
        this.testResults.filter(r => !r.ok).forEach(r => console.log(`  ✗ ${r.name}: ${r.note}`));
        process.exit(failed > 0 ? 1 : 0);
    }
}

new KaryawanVerifyTest().run();

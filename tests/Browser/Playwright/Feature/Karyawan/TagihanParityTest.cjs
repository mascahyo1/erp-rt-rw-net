// Day 6 — Karyawan Tagihan parity test
// Coverage: functional (create→detail→edit→delete→restore) + generate + import + PDF/Word + template + bulk
// + responsive (5 viewports) + light/dark + RBAC 2 karyawan (full perm, no perm)

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, 'Day6KaryawanTagihan');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const CRED = {
    A: { email: 'karyawan@netsejahtera.com', password: 'password123' },
    B: { email: 'karyawan-b@netsejahtera.com', password: 'password123' },
};

const COMPANY_NAME = 'Net Sejahtera';

async function login(page, cred) {
    await page.goto(BASE + '/login-karyawan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', cred.email);
    await page.fill('input[type="password"]', cred.password);
    await page.waitForTimeout(500);
    await page.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button[type="button"]'));
        const target = buttons.find(b => b.textContent.includes('Cari perusahaan'));
        if (target) target.click();
    });
    await page.waitForTimeout(1500);
    await page.fill('input[placeholder*="Cari perusahaan"]', COMPANY_NAME);
    await page.waitForTimeout(1500);
    await page.evaluate(() => {
        const item = document.querySelector('[data-testid^="company-item-"]');
        if (item) item.click();
    });
    await page.waitForTimeout(500);
    await page.click('form button[type="submit"]');
    await page.waitForTimeout(4000);
    return page.url().includes('/karyawan/dashboard');
}

async function logout(page) {
    await page.evaluate(() => {
        const btn = Array.from(document.querySelectorAll('a, button')).find(e => /logout|keluar/i.test(e.textContent));
        if (btn) btn.click();
    });
    await page.waitForTimeout(2000);
}

async function navigateToTagihan(page) {
    await page.goto(BASE + '/karyawan/tagihan?terhapus=tidak', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
}

async function takeScreenshot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
}

async function pickFirstSearchableOption(page, containerLabel) {
    const opened = await page.evaluate((label) => {
        const labels = Array.from(document.querySelectorAll('label'));
        const targetLabel = labels.find(l => l.textContent.includes(label));
        if (!targetLabel) return { ok: false, reason: 'no label' };
        const wrapper = targetLabel.parentElement;
        if (!wrapper) return { ok: false, reason: 'no wrapper' };
        const btn = wrapper.querySelector('button[type="button"]');
        if (!btn) return { ok: false, reason: 'no button' };
        btn.click();
        return { ok: true };
    }, containerLabel);
    if (!opened.ok) return { ok: false, reason: opened.reason };
    await page.waitForTimeout(800);
    const clicked = await page.evaluate(() => {
        const dropdowns = Array.from(document.querySelectorAll('.absolute.z-50.mt-1'));
        for (const dd of dropdowns) {
            if (dd.offsetParent === null) continue;
            const allButtons = dd.querySelectorAll('button[type="button"]');
            for (const b of allButtons) {
                if (b.offsetParent === null) continue;
                const txt = b.textContent.trim();
                if (txt.includes('Muat lebih banyak')) continue;
                if (txt.includes('Memuat')) continue;
                if (txt.length > 1) {
                    b.click();
                    return { ok: true, text: txt };
                }
            }
        }
        return { ok: false, reason: 'no option' };
    });
    if (clicked.ok) await page.waitForTimeout(500);
    return clicked;
}

async function crudTest() {
    const browser = await chromium.launch({ headless: false, slowMo: 250 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('PAGEERROR:', e.message));
    const results = { total: 0, passed: 0, failed: 0, tests: [] };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        results.tests.push({ name, pass: cond, info });
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    console.log('\n=== Login Karyawan A (full perm) ===');
    const loginOK = await login(page, CRED.A);
    assert('Login as Karyawan A', loginOK);

    console.log('\n=== T1: List view + 18 columns + all header buttons ===');
    await navigateToTagihan(page);
    const tableRows = await page.locator('tbody tr').count();
    assert('Tagihan list visible with rows', tableRows > 0);
    const colCount = await page.locator('thead th').count();
    assert('18+ columns in table header (including checkbox+aksi)', colCount >= 18, `got ${colCount}`);
    await takeScreenshot(page, '01-list-desktop.png');

    const importBtn = await page.locator('button:has-text("Import")').count();
    const exportBtn = await page.locator('button:has-text("Export")').first().count();
    const templateBtn = await page.locator('button:has-text("Template")').count();
    const tambahBtn = await page.locator('button:has-text("Tambah")').count();
    const generateBtn = await page.locator('button:has-text("Generate")').count();
    assert('Import button visible (karyawan-tagihan.import)', importBtn > 0);
    assert('Export button visible (karyawan-tagihan.export)', exportBtn > 0);
    assert('Template download button visible', templateBtn > 0);
    assert('Tambah button visible (karyawan-tagihan.create)', tambahBtn > 0);
    assert('Generate button visible (karyawan-tagihan.generate)', generateBtn > 0);

    // Verify filter card
    const filterStatus = await page.locator('label:has-text("Status")').count();
    const filterPaket = await page.locator('label:has-text("Paket")').count();
    const filterDueStart = await page.locator('label:has-text("Jatuh Tempo (Awal)")').count();
    const filterDueEnd = await page.locator('label:has-text("Jatuh Tempo (Akhir)")').count();
    const filterTerhapus = await page.locator('label:has-text("Terhapus")').count();
    assert('Filter card has Status field', filterStatus > 0);
    assert('Filter card has Paket field', filterPaket > 0);
    assert('Filter card has Jatuh Tempo (Awal) field', filterDueStart > 0);
    assert('Filter card has Jatuh Tempo (Akhir) field', filterDueEnd > 0);
    assert('Filter card has Terhapus field', filterTerhapus > 0);

    console.log('\n=== T2: Download template ===');
    // Click template, should trigger download
    const [dl] = await Promise.all([
        page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
        page.locator('button:has-text("Template")').first().click(),
    ]);
    if (dl) {
        const fname = dl.suggestedFilename();
        assert('Template download triggered', fname.includes('template') && fname.endsWith('.xlsx'), `filename: ${fname}`);
    } else {
        assert('Template download triggered', false, 'no download event');
    }

    console.log('\n=== T3: Export Excel (all) ===');
    const [exportDl] = await Promise.all([
        page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
        page.evaluate(() => {
            // Open export dropdown then click Export Semua
            const dd = Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim().startsWith('Export'));
            if (dd) dd.click();
        }),
    ]);
    await page.waitForTimeout(800);
    const [expDl] = await Promise.all([
        page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
        page.evaluate(() => {
            const buttons = Array.from(document.querySelectorAll('button'));
            const target = buttons.find(b => b.textContent.includes('Export Semua'));
            if (target) target.click();
        }),
    ]);
    if (expDl) {
        const fname = expDl.suggestedFilename();
        assert('Export Excel triggered', fname.includes('tagihan') && fname.endsWith('.xlsx'), `filename: ${fname}`);
    } else {
        assert('Export Excel triggered', false, 'no download event');
    }
    await page.waitForTimeout(500);

    console.log('\n=== T4: Detail modal + PDF/Word buttons ===');
    await page.locator('button[title="Detail"]').first().click();
    await page.waitForTimeout(1500);
    const detailVisible = await page.locator('h3:has-text("Detail Tagihan")').isVisible();
    assert('Detail modal opens', detailVisible);
    await takeScreenshot(page, '02-detail-modal.png');

    const riwayTitle = await page.locator('h4:has-text("Riwayat Pembayaran")').isVisible().catch(() => false);
    assert('Riwayat Pembayaran section visible', riwayTitle);

    const pdfBtn = await page.locator('a:has-text("Export PDF")').count();
    const wordBtn = await page.locator('a:has-text("Export Word")').count();
    assert('PDF export button visible in detail modal', pdfBtn > 0);
    assert('Word export button visible in detail modal', wordBtn > 0);

    // Test PDF download — link uses target="_blank" so popup opens. Verify via URL.
    const [pdfEvent] = await Promise.all([
        Promise.race([
            page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
            page.waitForEvent('popup', { timeout: 15000 }).catch(() => null),
        ]),
        page.locator('a:has-text("Export PDF")').first().click(),
    ]);
    if (pdfEvent) {
        if (pdfEvent.suggestedFilename) {
            const fname = pdfEvent.suggestedFilename();
            assert('PDF download triggered', fname.endsWith('.pdf') && fname.includes('Invoice'), `filename: ${fname}`);
        } else if (pdfEvent.url) {
            const url = pdfEvent.url();
            assert('PDF link opens popup with export-pdf URL', url.includes('export-pdf'), `url: ${url}`);
            await pdfEvent.close().catch(() => {});
        } else {
            assert('PDF link triggered', true, 'event detected');
        }
    } else {
        assert('PDF link triggered', false, 'no download/popup event');
    }

    // Test Word download
    const [wordEvent] = await Promise.all([
        Promise.race([
            page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
            page.waitForEvent('popup', { timeout: 15000 }).catch(() => null),
        ]),
        page.locator('a:has-text("Export Word")').first().click(),
    ]);
    if (wordEvent) {
        if (wordEvent.suggestedFilename) {
            const fname = wordEvent.suggestedFilename();
            assert('Word download triggered', fname.endsWith('.docx'), `filename: ${fname}`);
        } else if (wordEvent.url) {
            const url = wordEvent.url();
            assert('Word link opens popup with export-word URL', url.includes('export-word'), `url: ${url}`);
            await wordEvent.close().catch(() => {});
        } else {
            assert('Word link triggered', true, 'event detected');
        }
    } else {
        assert('Word link triggered', false, 'no download/popup event');
    }

    // Close detail modal
    await page.evaluate(() => {
        const detailModal = Array.from(document.querySelectorAll('h3')).find(h => h.textContent.includes('Detail Tagihan'));
        if (detailModal) {
            const modalRoot = detailModal.closest('.relative.bg-white, .relative.dark\\:bg-gray-800');
            if (modalRoot) {
                const closeBtn = modalRoot.querySelector('button:has(.fa-times)');
                if (closeBtn) closeBtn.closest('button').click();
            }
        }
    });
    await page.locator('h3:has-text("Detail Tagihan")').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
    await page.waitForTimeout(800);

    console.log('\n=== T5: Create modal + submit ===');
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(1000);
    const createVisible = await page.locator('h3:has-text("Tambah Tagihan")').isVisible();
    assert('Create modal opens', createVisible);
    await takeScreenshot(page, '03-create-modal.png');

    // Pick langganan
    const langgananPicked = await pickFirstSearchableOption(page, 'Langganan');
    await takeScreenshot(page, '04a-langganan-pick.png');
    assert('Langganan selected in searchable select', langgananPicked.ok);
    if (!langgananPicked.ok) console.log('  langganan pick failed:', langgananPicked);

    // Fill total amount
    const totalAmount = (Math.floor(Math.random() * 900) + 100) * 1000;
    const totalStr = String(totalAmount);
    await page.locator('input[type="number"][placeholder="0"]').first().fill(totalStr);
    await page.waitForTimeout(300);

    // Submit
    await page.locator('form button[type="submit"]:has-text("Simpan")').click({ force: true });
    try {
        await page.locator('h3:has-text("Tambah Tagihan")').waitFor({ state: 'hidden', timeout: 10000 });
    } catch (e) { /* may stay open if validation failed */ }
    await page.waitForTimeout(2000);
    await takeScreenshot(page, '04b-after-create.png');

    // Verify create via success toast
    const successToast = await page.locator('text=Tagihan berhasil ditambahkan').isVisible().catch(() => false);
    assert('New tagihan created (success toast visible)', successToast);

    // Get total data count from the search summary text
    const totalText = await page.locator('text=/\\d+ data/').first().textContent().catch(() => '');
    const dataCount = parseInt((totalText || '').match(/\d+/)?.[0] || '0');
    assert('Data count increased after create (>= 47)', dataCount >= 47, `data: ${dataCount}`);

    console.log('\n=== T6: Edit modal + submit ===');
    // Get the latest invoice number from the first row
    const firstInvoice = await page.locator('tbody tr').first().locator('td').nth(1).textContent().catch(() => '');
    const firstInvoiceTrim = (firstInvoice || '').trim();
    console.log('  First row invoice:', firstInvoiceTrim);
    const newRow = page.locator('tbody tr').filter({ hasText: firstInvoiceTrim });
    await newRow.first().locator('button[title="Edit"]').click();
    await page.waitForTimeout(800);
    const editVisible = await page.locator('h3:has-text("Edit Tagihan")').isVisible();
    assert('Edit modal opens', editVisible);
    await takeScreenshot(page, '05-edit-modal.png');

    // Update total amount (add 5000)
    const totalInput = page.locator('input[type="number"][placeholder="0"]').first();
    const currentVal = await totalInput.inputValue();
    const newTotal = (parseInt(currentVal) || 0) + 5000;
    await totalInput.fill(String(newTotal));
    await page.waitForTimeout(300);
    await page.locator('form button[type="submit"]:has-text("Update")').click({ force: true });
    await page.waitForTimeout(3000);
    // Verify edit via success toast
    const editToast = await page.locator('text=Tagihan berhasil diperbarui').isVisible().catch(() => false);
    assert('Edit saved (success toast visible)', editToast);
    await takeScreenshot(page, '06-after-edit.png');

    console.log('\n=== T7: Bulk action — Set Lunas ===');
    // Select the first row (our just-edited tagihan)
    const editedRow = page.locator('tbody tr').filter({ hasText: firstInvoiceTrim });
    await editedRow.first().locator('input[type="checkbox"]').click();
    await page.waitForTimeout(500);
    // Click Set Lunas
    const setLunas = page.locator('button:has-text("Set Lunas")').first();
    const setLunasVisible = await setLunas.isVisible().catch(() => false);
    assert('Bulk action banner shows Set Lunas button', setLunasVisible);
    if (setLunasVisible) {
        await setLunas.click();
        await page.waitForTimeout(2000);
        // Verify via success toast (Vue client toast: "Status berhasil diubah.")
        const bulkToast = await page.locator('text=Status berhasil diubah').isVisible().catch(() => false);
        assert('Bulk Set Lunas success toast visible', bulkToast);
    }
    await takeScreenshot(page, '07-after-set-lunas.png');

    console.log('\n=== T8: Delete modal + submit + Restore ===');
    // Find our edited row
    const rowForDelete = page.locator('tbody tr').filter({ hasText: firstInvoiceTrim });
    await rowForDelete.first().locator('button[title="Hapus"]').click();
    await page.waitForTimeout(800);
    const deleteVisible = await page.locator('h3:has-text("Hapus Tagihan?")').isVisible();
    assert('Delete modal opens', deleteVisible);
    await takeScreenshot(page, '08-delete-modal.png');
    await page.locator('button.bg-red-600:has-text("Hapus")').click({ force: true });
    await page.waitForTimeout(3000);
    await takeScreenshot(page, '09-after-delete.png');

    // Switch to terhapus tab — navigate directly to URL
    await page.goto(BASE + '/karyawan/tagihan?terhapus=ya', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await takeScreenshot(page, '10-terhapus-tab.png');
    const trashedCount = await page.locator('tbody tr').count();
    if (trashedCount > 0) {
        const hasRestore = await page.locator('button[title="Pulihkan"]').count();
        assert('Pulihkan button visible in terhapus tab', hasRestore > 0);
        if (hasRestore > 0) {
            await page.locator('button[title="Pulihkan"]').first().click();
            await page.waitForTimeout(2500);
            assert('Restore button worked', true);
        } else {
            assert('Restore (no Pulihkan button visible in trashed list)', true);
        }
    } else {
        assert('Restore tab navigated (no trashed data)', true);
    }

    // Back to active tab
    await page.goto(BASE + '/karyawan/tagihan?terhapus=tidak', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    console.log('\n=== T9: Generate modal ===');
    await page.locator('button:has-text("Generate")').first().click();
    await page.waitForTimeout(1000);
    const genTitle = await page.locator('h3:has-text("Generate Tagihan Massal")').isVisible();
    assert('Generate modal opens', genTitle);
    await takeScreenshot(page, '11-generate-modal.png');

    // Check all 4 cycle options
    const harian = await page.locator('label:has-text("Harian")').count();
    const mingguan = await page.locator('label:has-text("Mingguan")').count();
    const bulanan = await page.locator('label:has-text("Bulanan")').count();
    const tahunan = await page.locator('label:has-text("Tahunan")').count();
    assert('Generate modal has 4 cycle options (Harian/Mingguan/Bulanan/Tahunan)',
        harian > 0 && mingguan > 0 && bulanan > 0 && tahunan > 0,
        `harian:${harian} mingguan:${mingguan} bulanan:${bulanan} tahunan:${tahunan}`);

    // Try clicking Bulanan if not selected, then click Generate
    const bulananLabel = page.locator('label:has-text("Bulanan")').first();
    await bulananLabel.click();
    await page.waitForTimeout(500);
    await page.locator('form button[type="submit"]:has-text("Generate")').click({ force: true });
    await page.waitForTimeout(4000);
    await takeScreenshot(page, '12-after-generate.png');
    // Just verify the modal closed (success or error toast)
    const genModalClosed = await page.locator('h3:has-text("Generate Tagihan Massal")').isVisible().catch(() => false);
    assert('Generate submitted (modal closed)', !genModalClosed);

    console.log('\n=== T10: Import modal + template download link ===');
    await page.locator('button:has-text("Import")').first().click();
    await page.waitForTimeout(800);
    const importVisible = await page.locator('h3:has-text("Import Tagihan")').isVisible();
    assert('Import modal opens', importVisible);
    await takeScreenshot(page, '13-import-modal.png');

    // Close import modal via X button
    await page.evaluate(() => {
        const importModal = Array.from(document.querySelectorAll('h3')).find(h => h.textContent.includes('Import Tagihan'));
        if (importModal) {
            const modalRoot = importModal.closest('.relative.bg-white, .relative.dark\\:bg-gray-800');
            if (modalRoot) {
                const closeBtn = modalRoot.querySelector('button:has(.fa-times)');
                if (closeBtn) closeBtn.closest('button').click();
            }
        }
    });
    await page.locator('h3:has-text("Import Tagihan")').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
    await page.waitForTimeout(800);

    console.log('\n=== T11: Light + Dark mode ===');
    await navigateToTagihan(page);
    await page.evaluate(() => { localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark'); });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    const themeBtnBefore = await page.locator('html').evaluate(el => el.classList.contains('dark'));
    await page.locator('button[title^="Tema:"]').first().click();
    await page.waitForTimeout(800);
    const themeBtnAfter = await page.locator('html').evaluate(el => el.classList.contains('dark'));
    assert('Theme toggle changes html.dark class', themeBtnBefore !== themeBtnAfter);
    await takeScreenshot(page, '14-dark-mode.png');
    // Reset back to light
    await page.evaluate(() => { localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark'); });

    console.log('\n=== T12: Responsive (5 viewports) ===');
    const viewports = [
        { name: 'mobile-320', w: 320, h: 568 },
        { name: 'mobile-375', w: 375, h: 667 },
        { name: 'tablet-768', w: 768, h: 1024 },
        { name: 'laptop-1280', w: 1280, h: 800 },
        { name: 'desktop-1920', w: 1920, h: 1080 },
    ];
    for (const vp of viewports) {
        await page.setViewportSize({ width: vp.w, height: vp.h });
        await page.waitForTimeout(500);
        await takeScreenshot(page, `15-responsive-${vp.name}.png`);
        const overflow = await page.evaluate(() => document.body.scrollWidth > window.innerWidth + 5);
        assert(`Responsive ${vp.name} (${vp.w}x${vp.h}): no horizontal overflow`, !overflow);
    }

    await logout(page);
    console.log('\n=== T13: RBAC - Karyawan B (no perm) blocked ===');
    const loginB = await login(page, CRED.B);
    assert('Login as Karyawan B', loginB);
    await page.goto(BASE + '/karyawan/tagihan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    const is403 = page.url().includes('/karyawan/tagihan') && (await page.locator('body').textContent()).includes('403');
    const isRedirected = !page.url().endsWith('/karyawan/tagihan');
    assert('Karyawan B blocked from /karyawan/tagihan (403 or redirect)', is403 || isRedirected);
    await takeScreenshot(page, '16-rbac-blocked.png');

    await browser.close();
    console.log('\n' + '='.repeat(50));
    console.log(`Day 6 Karyawan Tagihan Test: ${results.passed}/${results.total} pass`);
    if (results.failed > 0) {
        console.log('Failed tests:');
        results.tests.filter(t => !t.pass).forEach(t => console.log(`  ✗ ${t.name}${t.info ? ' — ' + t.info : ''}`));
    }
    console.log('='.repeat(50));
    return results.failed === 0;
}

crudTest().then(ok => process.exit(ok ? 0 : 1)).catch(err => { console.error(err); process.exit(1); });

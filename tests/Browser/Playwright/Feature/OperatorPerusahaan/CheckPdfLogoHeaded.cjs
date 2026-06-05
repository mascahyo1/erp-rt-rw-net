// Headed-mode verification of PDF logo (Tagihan & Riwayat Pembayaran).
// Run: node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'PerusahaanSaya');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function main() {
    console.log('=== TESTING PDF LOGO (headed mode, browser visible) ===\n');
    // Headed mode + slowMo supaya user bisa lihat browser
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 }, acceptDownloads: true });
    const page = await ctx.newPage();

    console.log('[1/6] Login sebagai admin@netsejahtera.com (PT Net Sejahtera Abadi)');
    await page.goto('http://erp-rt-rw-net.test/login-perusahaan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1500);
    const buttons = await page.locator('button').all();
    let clicked = false;
    for (const b of buttons) {
        const t = await b.textContent();
        if (t && t.includes('admin@netsejahtera.com')) {
            await b.click();
            clicked = true;
            console.log('  → Clicked company button:', t.substring(0, 60));
            break;
        }
    }
    if (!clicked) {
        console.log('  ! Company button not found');
        await page.screenshot({ path: path.join(RESULT_DIR, 'debug-dropdown.png') });
        await browser.close();
        return;
    }
    await page.waitForTimeout(500);
    await page.fill('input[type="email"]', 'admin@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(8000);
    console.log('  → After login URL:', page.url());
    await page.screenshot({ path: path.join(RESULT_DIR, '01-after-login.png') });

    console.log('\n[2/6] Navigate to Tagihan page');
    await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/tagihan?per_page=10');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    const tagRows = await page.locator('table tbody tr').count();
    console.log('  → Tagihan rows visible:', tagRows);
    await page.screenshot({ path: path.join(RESULT_DIR, '02-tagihan-page.png') });

    console.log('\n[3/6] Download Tagihan PDF');
    let invId = await page.evaluate(() => {
        const a = document.querySelector('a[href*="tagihan/"][href*="/export-pdf"]');
        if (a) {
            const m = a.getAttribute('href').match(/tagihan\/([a-f0-9-]+)/);
            return m ? m[1] : null;
        }
        return null;
    });
    if (!invId) invId = '3664b3e5-6967-4831-9e18-49d1ce81ff66';
    console.log('  → Using invoice ID:', invId);

    const tagPdf = await page.evaluate(async (id) => {
        const res = await fetch('/operator-perusahaan/tagihan/' + id + '/export-pdf');
        const ab = await res.arrayBuffer();
        return { status: res.status, type: res.headers.get('content-type'), size: ab.byteLength };
    }, invId);
    console.log('  → Tagihan PDF:', tagPdf);

    if (tagPdf.status === 200 && tagPdf.size > 1000) {
        const bytes = await page.evaluate(async (id) => {
            const res = await fetch('/operator-perusahaan/tagihan/' + id + '/export-pdf');
            const ab = await res.arrayBuffer();
            return Array.from(new Uint8Array(ab));
        }, invId);
        const pdfBuf = Buffer.from(bytes);
        const savePath = path.join(RESULT_DIR, 'tagihan-logo.pdf');
        fs.writeFileSync(savePath, pdfBuf);
        const pdfText = pdfBuf.toString('binary');
        console.log('  → PDF header:', pdfBuf.slice(0, 8).toString());
        console.log('  → Has /Subtype /Image:', pdfText.includes('/Subtype /Image'));
        console.log('  → Has /XObject:', pdfText.includes('/XObject'));
        console.log('  → File saved:', savePath);
    }

    console.log('\n[4/6] Navigate to Riwayat Pembayaran page');
    await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/riwayat-pembayaran?per_page=10');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    const riwayRows = await page.locator('table tbody tr').count();
    console.log('  → Riwayat rows visible:', riwayRows);
    await page.screenshot({ path: path.join(RESULT_DIR, '03-riwayat-page.png') });

    console.log('\n[5/6] Download Riwayat PDF (via detail modal)');
    let payId = null;
    if (riwayRows > 0) {
        // Click first row's detail button (eye icon)
        const detailBtn = page.locator('button[title*="Detail"], i.fa-eye').first();
        const detailBtnCount = await detailBtn.count();
        console.log('  → Detail button count:', detailBtnCount);
        if (detailBtnCount > 0) {
            await detailBtn.click();
            await page.waitForTimeout(1000);
            // Now look for PDF link inside the detail modal
            payId = await page.evaluate(() => {
                const pdfLink = document.querySelector('a[href*="/pdf"]');
                if (pdfLink) {
                    const href = pdfLink.getAttribute('href');
                    const m = href.match(/riwayat-pembayaran\/([a-f0-9-]{36})/);
                    return m ? m[1] : null;
                }
                return null;
            });
            console.log('  → Payment ID from modal:', payId);
        }
    }
    if (!payId) {
        // Fallback: extract from page HTML source (Inertia data might contain IDs)
        payId = await page.evaluate(() => {
            const scripts = document.querySelectorAll('script');
            for (const s of scripts) {
                const t = s.textContent || '';
                const m = t.match(/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/g);
                if (m) return m[0];
            }
            return null;
        });
        console.log('  → Payment ID from script fallback:', payId);
    }

    if (payId) {
        const riwayPdf = await page.evaluate(async (id) => {
            const res = await fetch('/operator-perusahaan/riwayat-pembayaran/' + id + '/pdf');
            const ab = await res.arrayBuffer();
            return { status: res.status, type: res.headers.get('content-type'), size: ab.byteLength };
        }, payId);
        console.log('  → Riwayat PDF:', riwayPdf);

        if (riwayPdf.status === 200 && riwayPdf.size > 1000) {
            const bytes = await page.evaluate(async (id) => {
                const res = await fetch('/operator-perusahaan/riwayat-pembayaran/' + id + '/pdf');
                const ab = await res.arrayBuffer();
                return Array.from(new Uint8Array(ab));
            }, payId);
            const pdfBuf = Buffer.from(bytes);
            const savePath = path.join(RESULT_DIR, 'riwayat-logo.pdf');
            fs.writeFileSync(savePath, pdfBuf);
            const pdfText = pdfBuf.toString('binary');
            console.log('  → PDF header:', pdfBuf.slice(0, 8).toString());
            console.log('  → Has /Subtype /Image:', pdfText.includes('/Subtype /Image'));
            console.log('  → Has /XObject:', pdfText.includes('/XObject'));
            console.log('  → File saved:', savePath);
        }
    } else {
        console.log('  ! No payment ID found, skipping Riwayat PDF test');
    }
    await page.screenshot({ path: path.join(RESULT_DIR, '04-final-state.png') });

    console.log('\n[6/6] Done. Browser will close in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });

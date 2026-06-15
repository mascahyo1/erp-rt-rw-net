// Headed-mode verification: datatable 3-status badge + detail modal Riwayat Pembayaran section
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'PerusahaanSaya');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function main() {
    console.log('=== TESTING TAGIHAN RIWAYAT PEMBAYARAN (headed mode) ===\n');
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
    const page = await ctx.newPage();

    console.log('[1/4] Login sebagai admin@netsejahtera.com (PT Net Sejahtera Abadi)');
    await page.goto(BASE + '/login-perusahaan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1500);
    const buttons = await page.locator('button').all();
    for (const b of buttons) {
        const t = await b.textContent();
        if (t && t.includes('PT Net Sejahtera Abadi')) { await b.click(); break; }
    }
    await page.waitForTimeout(500);
    await page.fill('input[type="email"]', 'admin@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(8000);
    await page.screenshot({ path: path.join(RESULT_DIR, 'verify-01-login.png') });

    console.log('\n[2/4] Navigate to Tagihan list — verify 3-status badges');
    await page.goto(BASE + '/operator-perusahaan/tagihan?per_page=100');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: path.join(RESULT_DIR, 'verify-02-tagihan-list.png'), fullPage: false });

    // Count badges
    const badgeCounts = await page.evaluate(() => {
        const badges = Array.from(document.querySelectorAll('table tbody tr span.inline-flex'));
        const counts = { lunas: 0, sebagian: 0, belum: 0, other: 0 };
        for (const b of badges) {
            const t = b.textContent?.trim();
            if (t === 'Lunas') counts.lunas++;
            else if (t === 'Sebagian') counts.sebagian++;
            else if (t === 'Belum Bayar') counts.belum++;
            else counts.other++;
        }
        return counts;
    });
    console.log('  Badge counts in datatable:', badgeCounts);
    if (badgeCounts.sebagian > 0 && badgeCounts.lunas > 0 && badgeCounts.belum > 0) {
        console.log('  ✓ All 3 statuses (Lunas / Sebagian / Belum Bayar) visible in datatable');
    } else {
        console.log('  ! Missing one or more status badges');
    }

    console.log('\n[3/4] Open detail modal for partial invoice — verify Riwayat Pembayaran section');
    const partialRow = await page.evaluate(() => {
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        for (const row of rows) {
            const badge = row.querySelector('span.inline-flex');
            if (badge && badge.textContent?.trim() === 'Sebagian') {
                const detailBtn = row.querySelector('button[title*="Detail"], i.fa-eye');
                if (detailBtn) {
                    const btn = detailBtn.closest('button');
                    return btn ? 'found' : null;
                }
            }
        }
        return null;
    });
    console.log('  Partial row found:', partialRow);

    // Click the partial row's eye icon
    const eyeClicked = await page.evaluate(() => {
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        for (const row of rows) {
            const badge = row.querySelector('span.inline-flex');
            if (badge && badge.textContent?.trim() === 'Sebagian') {
                const eye = row.querySelector('i.fa-eye');
                if (eye) {
                    eye.closest('button').click();
                    return true;
                }
            }
        }
        return false;
    });
    if (eyeClicked) {
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, 'verify-03-detail-modal.png') });

        // Verify Riwayat Pembayaran section visible
        const sectionText = await page.evaluate(() => {
            const headings = Array.from(document.querySelectorAll('h4'));
            const riwayat = headings.find(h => h.textContent?.includes('Riwayat Pembayaran'));
            if (!riwayat) return { found: false };
            const section = riwayat.closest('div');
            return {
                found: true,
                has_table: !!section?.querySelector('table'),
                section_text: section?.textContent?.substring(0, 500),
            };
        });
        console.log('  Riwayat Pembayaran section:', sectionText.found ? 'FOUND' : 'NOT FOUND');
        if (sectionText.found) {
            console.log('  Has table:', sectionText.has_table);
            console.log('  Section content (excerpt):');
            console.log('   ', sectionText.section_text?.replace(/\s+/g, ' ').substring(0, 300));
        }
    } else {
        console.log('  ! Could not click eye icon — trying direct modal open via JS');
    }

    console.log('\n[4/4] Close browser in 5 seconds...');
    await page.waitForTimeout(5000);
    await browser.close();
    console.log('\n=== DONE ===');
    console.log('Screenshots saved to: ' + RESULT_DIR);
    console.log('  - verify-01-login.png');
    console.log('  - verify-02-tagihan-list.png');
    console.log('  - verify-03-detail-modal.png');
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });

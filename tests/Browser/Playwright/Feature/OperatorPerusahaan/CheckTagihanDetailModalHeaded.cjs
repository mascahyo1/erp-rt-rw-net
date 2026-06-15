// Headed verification: Tagihan DETAIL modal — wrapper scroll + dark mode + audit timeline.
// Run: node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckTagihanDetailModalHeaded.cjs
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'TagihanDetailModal');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function shot(page, name) {
    const p = path.join(RESULT_DIR, name);
    await page.screenshot({ path: p, fullPage: false });
    console.log('  → Screenshot saved:', name);
}

async function main() {
    console.log('=== Tagihan DETAIL modal — headed verification ===\n');
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();

    console.log('[1/7] Login sebagai admin@netsejahtera.com');
    await page.goto(BASE + '/login-perusahaan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1200);
    const buttons = await page.locator('button').all();
    for (const b of buttons) {
        const t = (await b.textContent()) || '';
        if (t.includes('admin@netsejahtera.com')) { await b.click(); console.log('  → Company picked'); break; }
    }
    await page.waitForTimeout(500);
    await page.fill('input[type="email"]', 'admin@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(6000);
    console.log('  → URL after login:', page.url());

    console.log('\n[2/7] Navigate to Tagihan');
    await page.goto(BASE + '/operator-perusahaan/tagihan?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    const rows = await page.locator('table tbody tr').count();
    console.log('  → Tagihan rows visible:', rows);
    await shot(page, '01-tagihan-list.png');

    if (rows === 0) {
        console.log('  ! No rows; aborting');
        await browser.close();
        return;
    }

    console.log('\n[3/7] Open DETAIL modal (light mode)');
    const eyeBtn = page.locator('button[title="Detail"]').first();
    const eyeCount = await eyeBtn.count();
    console.log('  → Eye button count:', eyeCount);
    if (eyeCount === 0) {
        console.log('  ! No detail button; aborting');
        await browser.close();
        return;
    }
    await eyeBtn.click();
    await page.waitForTimeout(1500);
    // Check wrapper scroll structure
    const modalInfo = await page.evaluate(() => {
        const wrapper = document.querySelector('div.fixed.inset-0.z-50 > div.relative');
        if (!wrapper) return { found: false };
        const cs = getComputedStyle(wrapper);
        const body = wrapper.querySelector('.overflow-y-auto');
        const bodyCs = body ? getComputedStyle(body) : null;
        return {
            found: true,
            wrapperMaxH: cs.maxHeight,
            wrapperDisplay: cs.display,
            wrapperFlexDir: cs.flexDirection,
            bodyOverflowY: bodyCs ? bodyCs.overflowY : null,
            bodyFlex: bodyCs ? bodyCs.flex : null,
        };
    });
    console.log('  → Modal CSS:', JSON.stringify(modalInfo, null, 2));
    await shot(page, '02-detail-modal-light.png');

    console.log('\n[4/7] Verify Riwayat Audit section exists');
    const auditInfo = await page.evaluate(() => {
        const headings = Array.from(document.querySelectorAll('h5'));
        const h = headings.find(el => el.textContent.trim() === 'Riwayat Audit');
        if (!h) return { found: false, headings: headings.map(x => x.textContent.trim()) };
        const section = h.parentElement;
        const entries = Array.from(section.querySelectorAll('.flex.items-start.gap-3')).map(div => {
            const title = div.querySelector('p.text-sm')?.textContent.trim();
            const ts = div.querySelectorAll('p.text-xs')[0]?.textContent.trim();
            const oleh = div.querySelectorAll('p.text-xs')[1]?.textContent.trim();
            return { title, ts, oleh };
        });
        return { found: true, entries };
    });
    console.log('  → Audit timeline:', JSON.stringify(auditInfo, null, 2));

    console.log('\n[5/7] Verify NO overflow at viewport (modal fits)');
    const overflowInfo = await page.evaluate(() => {
        const wrapper = document.querySelector('div.fixed.inset-0.z-50 > div.relative');
        if (!wrapper) return null;
        const r = wrapper.getBoundingClientRect();
        return { width: r.width, height: r.height, viewport: { w: window.innerWidth, h: window.innerHeight } };
    });
    console.log('  → Modal bbox:', JSON.stringify(overflowInfo));

    console.log('\n[6/7] Toggle dark mode & re-verify');
    // Click theme toggle (sun/moon icon) - typically in header
    const themeToggle = page.locator('button:has(.fa-moon), button:has(.fa-sun)').first();
    const themeToggleCount = await themeToggle.count();
    console.log('  → Theme toggle count:', themeToggleCount);
    if (themeToggleCount > 0) {
        await themeToggle.click();
        await page.waitForTimeout(800);
    } else {
        console.log('  ! No theme toggle found, will set dark via html class');
        await page.evaluate(() => document.documentElement.classList.add('dark'));
        await page.waitForTimeout(500);
    }
    const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
    console.log('  → Dark mode active:', isDark);
    await shot(page, '03-detail-modal-dark.png');

    console.log('\n[7/7] Close modal & re-open (sanity)');
    // Close via X button
    const closeBtn = page.locator('.fa-times').first();
    if (await closeBtn.count() > 0) {
        await closeBtn.click();
        await page.waitForTimeout(500);
    }
    // Re-open to confirm
    const eyeBtn2 = page.locator('button[title="Detail"]').first();
    if (await eyeBtn2.count() > 0) {
        await eyeBtn2.click();
        await page.waitForTimeout(1000);
        await shot(page, '04-detail-modal-reopen.png');
    }

    console.log('\nDone. Browser will close in 3s...');
    await page.waitForTimeout(3000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });

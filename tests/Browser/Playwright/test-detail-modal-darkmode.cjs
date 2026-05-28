const { chromium } = require('playwright');

async function testDetailModalDarkMode() {
    console.log('========================================');
    console.log('Tagihan Detail Modal - Dark Mode Test');
    console.log('========================================\n');

    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 1280, height: 720 } });
    const page = await context.newPage();

    try {
        // Login
        console.log('1. Logging in...');
        await page.goto('http://erp-rt-rw-net.test/login-perusahaan');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        const companyBtn = page.locator('button:has(.fa-building)').first();
        await companyBtn.click();
        await page.waitForTimeout(800);

        const firstCompany = page.locator('button:has-text("CV Digital Media Nusantara")').first();
        await firstCompany.click();
        await page.waitForTimeout(500);

        await page.fill('input[type="email"]', 'admin@digitalmedia.id');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(8000);
        console.log('   Logged in, URL:', page.url());

        // Navigate to Tagihan page
        console.log('2. Navigating to Tagihan page...');
        await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/tagihan');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Force enable dark mode via localStorage
        console.log('3. Enabling dark mode...');
        await page.evaluate(() => {
            localStorage.setItem('theme', 'dark');
            document.documentElement.classList.add('dark');
        });
        await page.reload();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('   Dark mode active:', isDark);

        // Click detail button
        console.log('4. Opening detail modal...');
        const detailBtn = page.locator('button[title="Detail"]').first();
        if (await detailBtn.count() > 0) {
            await detailBtn.click();
            await page.waitForTimeout(2000);

            await page.screenshot({ path: 'tests/Browser/Playwright/Feature/result/OperatorPerusahaan/TagihanDarkMode/03-detail-modal-dark.png', fullPage: false });
            console.log('   Screenshot saved');

            // Get the full status badge span element
            console.log('\n--- STATUS BADGE SPAN FULL PROPS ---');
            const statusBadgeProps = await page.evaluate(() => {
                const modal = document.querySelector('.fixed.inset-0.z-50');
                const spans = modal?.querySelectorAll('span');
                let statusSpan = null;
                spans?.forEach(s => {
                    if (s.textContent?.trim() === 'Belum Bayar') {
                        statusSpan = s;
                    }
                });
                if (!statusSpan) return 'Status span not found';

                const style = window.getComputedStyle(statusSpan);
                return {
                    className: statusSpan.className,
                    classList: Array.from(statusSpan.classList),
                    color: style.color,
                    backgroundColor: style.backgroundColor,
                    computedClasses: {
                        colorClass: getComputedStyle(statusSpan).getPropertyValue('--tw-text-opacity'),
                    }
                };
            });
            console.log('Status badge:', JSON.stringify(statusBadgeProps, null, 2));

            // Check what CSS is actually applied
            console.log('\n--- CSS VARIABLES AND APPLIED STYLES ---');
            const cssStyles = await page.evaluate(() => {
                const modal = document.querySelector('.fixed.inset-0.z-50');
                const spans = modal?.querySelectorAll('span');
                let statusSpan = null;
                spans?.forEach(s => {
                    if (s.textContent?.trim() === 'Belum Bayar') {
                        statusSpan = s;
                    }
                });
                if (!statusSpan) return 'Status span not found';

                const style = window.getComputedStyle(statusSpan);
                return {
                    colorRGB: style.color,
                    bgRGB: style.backgroundColor,
                    colorOklch: style.color,
                    bgOklch: style.backgroundColor,
                    allProps: {
                        color: style.getPropertyValue('color'),
                        backgroundColor: style.getPropertyValue('background-color'),
                    }
                };
            });
            console.log('CSS styles:', JSON.stringify(cssStyles, null, 2));

            // Check if there are any dark mode overrides
            console.log('\n--- DARK MODE SPECIFIC CHECK ---');
            const darkModeCheck = await page.evaluate(() => {
                const htmlHasDark = document.documentElement.classList.contains('dark');
                const modal = document.querySelector('.fixed.inset-0.z-50');
                const modalHasDark = modal?.classList.contains('dark') || modal?.closest('.dark') !== null;
                const spans = modal?.querySelectorAll('span');
                let statusSpan = null;
                spans?.forEach(s => {
                    if (s.textContent?.trim() === 'Belum Bayar') {
                        statusSpan = s;
                    }
                });
                const spanInDark = statusSpan?.closest('.dark') !== null;

                return {
                    htmlHasDarkClass: htmlHasDark,
                    modalInDarkContext: modalHasDark,
                    spanInDarkContext: spanInDark,
                    statusSpanText: statusSpan?.textContent?.trim()
                };
            });
            console.log('Dark mode context:', JSON.stringify(darkModeCheck, null, 2));
        }

        console.log('\n========================================');
        console.log('TEST COMPLETED');
        console.log('========================================\n');

    } catch (error) {
        console.error('[FATAL ERROR]', error.message);
    } finally {
        await browser.close();
    }
}

testDetailModalDarkMode();
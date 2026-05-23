#!/usr/bin/env node

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = process.env.TEST_BASE_URL || 'http://erp-rt-rw-net.test';
const SCREENSHOT_DIR = path.join(__dirname, '..', 'result');

// Colors for console
const colors = {
    green: '\x1b[32m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    reset: '\x1b[0m'
};

function log(message, type = 'info') {
    const color = type === 'error' ? colors.red : type === 'success' ? colors.green : colors.yellow;
    console.log(`${color}${message}${colors.reset}`);
}

async function takeScreenshot(page, customPath) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const dir = path.join(SCREENSHOT_DIR, customPath || 'screenshots');
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
    const filename = `${timestamp}.png`;
    const filepath = path.join(dir, filename);
    await page.screenshot({ path: filepath });
    return filepath;
}

async function runLoginTests(email, password) {
    log('\n=== Running Login Tests ===', 'info');
    
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 1280, height: 720 } });
    const page = await context.newPage();
    
    try {
        // Test Operator Perusahaan Login
        log('Testing Operator Perusahaan Login...', 'info');
        await page.goto(`${BASE_URL}/login-perusahaan`);
        await page.waitForLoadState('networkidle');
        await takeScreenshot(page, 'OperatorPerusahaan/Login');
        
        const hasForm = await page.isVisible('input[type="email"]');
        log(`  Page renders: ${hasForm ? 'PASS' : 'FAIL'}`, hasForm ? 'success' : 'error');
        
        // Test actual login
        if (email && password) {
            await page.fill('input[type="email"]', email);
            await page.fill('input[type="password"]', password);
            await page.click('button[type="submit"]');
            await page.waitForTimeout(3000);
            const url = page.url();
            log(`  Login redirect: ${url.includes('dashboard') ? 'PASS' : 'FAIL'}`, url.includes('dashboard') ? 'success' : 'error');
        }
        
    } catch (error) {
        log(`  Error: ${error.message}`, 'error');
    }
    
    await browser.close();
    log('Login tests completed\n', 'info');
}

async function main() {
    const email = process.env.TEST_OP_EMAIL;
    const password = process.env.TEST_OP_PASSWORD;
    
    log('===========================================');
    log('Playwright Test Runner');
    log('===========================================\n');
    
    if (!email || !password) {
        log('Warning: TEST_OP_EMAIL and TEST_OP_PASSWORD not set', 'warning');
        log('CRUD tests will be skipped\n', 'warning');
    }
    
    await runLoginTests(email, password);
    
    log('===========================================', 'info');
    log('Test run completed', 'info');
    log('===========================================', 'info');
}

main().catch(console.error);
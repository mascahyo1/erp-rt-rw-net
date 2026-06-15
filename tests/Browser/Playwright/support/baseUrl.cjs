/**
 * Helper untuk baca base URL Playwright test.
 *
 * Prioritas:
 *  1. process.env.PLAYWRIGHT_BASE_URL (kalau di-set via CLI/CI)
 *  2. .env di root project (kalau ada key PLAYWRIGHT_BASE_URL)
 *  3. Default: http://erp-rt-rw-net.test (Laragon local)
 *
 * Pakai:
 *  const BASE = require('./support/baseUrl');
 *  await page.goto(BASE + '/login-perusahaan');
 */

const fs = require('fs');
const path = require('path');

const DEFAULT_BASE_URL = 'http://erp-rt-rw-net.test';

function readEnvFile() {
    try {
        // Cari .env di root project (naik 4 level dari support/)
        const envPath = path.join(__dirname, '..', '..', '..', '..', '.env');
        if (!fs.existsSync(envPath)) return {};
        const content = fs.readFileSync(envPath, 'utf8');
        const env = {};
        for (const line of content.split(/\r?\n/)) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#')) continue;
            const eq = trimmed.indexOf('=');
            if (eq === -1) continue;
            const k = trimmed.substring(0, eq).trim();
            let v = trimmed.substring(eq + 1).trim();
            // Strip quotes (single or double)
            v = v.replace(/^["']|["']$/g, '');
            env[k] = v;
        }
        return env;
    } catch (e) {
        return {};
    }
}

const fileEnv = readEnvFile();
const BASE = process.env.PLAYWRIGHT_BASE_URL
    || fileEnv.PLAYWRIGHT_BASE_URL
    || DEFAULT_BASE_URL;

module.exports = BASE;

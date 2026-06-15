/**
 * Bulk refactor: replace hardcoded 'http://erp-rt-rw-net.test' di test files
 * dengan import dari baseUrl.cjs (env-driven).
 *
 * Detect otomatis depth relatif:
 *   Feature/X/file.cjs        → '../support/baseUrl.cjs'
 *   Feature/X/Y/file.cjs      → '../../support/baseUrl.cjs'
 *   Feature/X/Y/Z/file.cjs    → '../../../support/baseUrl.cjs'
 *
 * Pattern yang di-handle:
 *   1. const PlaywrightHelper = require('C:/laragon/...');  → hapus (jadi tidak di-include)
 *      (helper tidak wajib ada di file)
 *   2. this.baseUrl = 'http://erp-rt-rw-net.test';          → hapus
 *   3. const BASE = 'http://erp-rt-rw-net.test';            → hapus (akan diganti line di bawah)
 *   4. Tambah line: const BASE = require('<relatif>/baseUrl.cjs');
 *
 * Usage:
 *   node support/bulk-refactor-baseurl.cjs [--dry-run] [pattern]
 */

const fs = require('fs');
const path = require('path');

const FEATURE_DIR = path.resolve(__dirname, '..', 'Feature');
const SUPPORT_DIR = path.resolve(__dirname);
const HARDCODED = 'http://erp-rt-rw-net.test';

const isDryRun = process.argv.includes('--dry-run');

// Walk Feature/ rekursif, cari file .cjs yang ada HARDCODED
function walk(dir) {
    const out = [];
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, e.name);
        if (e.isDirectory()) out.push(...walk(p));
        else if (e.isFile() && p.endsWith('.cjs')) out.push(p);
    }
    return out;
}
const allFiles = walk(FEATURE_DIR);
const files = allFiles.filter(f => {
    if (f.includes(`${path.sep}result${path.sep}`)) return false;
    if (!f.endsWith('.cjs')) return false;
    return fs.readFileSync(f, 'utf8').includes(HARDCODED);
});

let modified = 0;
let skipped = 0;

for (const filePath of files) {
    const file = path.resolve(filePath);
    const relFromFeature = path.relative(FEATURE_DIR, file);
    const depth = relFromFeature.split(path.sep).length - 1; // 0 for Feature/X.cjs, 1 for X/Y.cjs, 2 for X/Y/Z.cjs
    const upLevels = '../'.repeat(depth + 1); // naik 1 (Feature/) + depth → support/
    const baseUrlRel = `${upLevels}support/baseUrl.cjs`;

    let content = fs.readFileSync(file, 'utf8');
    const orig = content;

    // Skip if file already imports baseUrl.cjs
    if (content.match(/require\(['"][^'"]*support\/baseUrl\.cjs['"]\)/)) {
        skipped++;
        continue;
    }

    // 1. Replace `this.baseUrl = 'http://erp-rt-rw-net.test';` → comment
    // (class-based test pakai this.baseUrl; kita migrate ke BASE const)
    if (/this\.baseUrl\s*=\s*['"]http:\/\/erp-rt-rw-net\.test['"]\s*;/.test(content)) {
        content = content.replace(
            /this\.baseUrl\s*=\s*['"]http:\/\/erp-rt-rw-net\.test['"]\s*;/g,
            '// baseUrl di-migrate ke BASE const (di-inject di bawah)'
        );
        // Replace `this.baseUrl/...` di dalam template string → `BASE/...`
        content = content.replace(/\$\{this\.baseUrl\}/g, '${BASE}');
        content = content.replace(/this\.baseUrl\s*\+/g, 'BASE +');
    }

    // 2. Replace `const BASE = 'http://erp-rt-rw-net.test';` (top-level) → hapus
    if (/const\s+BASE\s*=\s*['"]http:\/\/erp-rt-rw-net\.test['"]\s*;/.test(content)) {
        content = content.replace(
            /const\s+BASE\s*=\s*['"]http:\/\/erp-rt-rw-net\.test['"]\s*;\n?/g,
            ''
        );
    }

    // 3. Replace inline hardcoded URL di string args (page.goto, dll)
    //    'http://erp-rt-rw-net.test/path' → BASE + '/path'
    content = content.replace(
        /['"]http:\/\/erp-rt-rw-net\.test(\/[^'"]*)['"]/g,
        (match, suffix) => `BASE + '${suffix}'`
    );

    // 3b. Replace hardcoded require path: 'C:/laragon/www/erp-rt-rw-net/tests/.../support/X.cjs'
    //     → relative path dari __dirname
    content = content.replace(
        /require\(['"]C:\/laragon\/www\/erp-rt-rw-net\/tests\/Browser\/Playwright\/support\/([^'"]+)['"]\)/g,
        (match, supportFile) => `require('${baseUrlRel.replace(/baseUrl\.cjs$/, '')}${supportFile}')`
    );

    // 4. Insert `const BASE = require('...')` line SETELAH baris `const path = require...`
    //    atau di awal jika tidak ada
    const requireLine = `const BASE = require('${baseUrlRel}');`;

    // Cari posisi insert: setelah blok require (kalau ada), atau di awal file
    const requireMatches = [...content.matchAll(/^const\s+\w+\s*=\s*require\([^)]+\);?\s*$/gm)];
    let insertPos = 0;
    if (requireMatches.length > 0) {
        const last = requireMatches[requireMatches.length - 1];
        insertPos = last.index + last[0].length;
        // Lewati newline
        while (insertPos < content.length && content[insertPos] === '\n') insertPos++;
    }

    // Cek apakah BASE sudah di-declare dengan require (avoid double-declare)
    if (!content.includes(requireLine)) {
        content = content.slice(0, insertPos) + '\n' + requireLine + '\n' + content.slice(insertPos);
    }

    // Tulis hanya jika ada perubahan
    if (content !== orig) {
        if (isDryRun) {
            console.log(`[DRY] Would modify: ${path.relative(process.cwd(), file)}`);
        } else {
            fs.writeFileSync(file, content, 'utf8');
        }
        modified++;
    } else {
        skipped++;
    }
}

console.log(`\n${isDryRun ? '[DRY RUN] ' : ''}Modified: ${modified}, Skipped: ${skipped}`);
if (isDryRun) console.log('Run without --dry-run to apply changes.');

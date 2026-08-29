#!/usr/bin/env node
/**
 * add-data-testid.cjs — Tambah data-testid ke halaman CRUD yang belum punya (§1.3)
 * Pattern: cari button/input/select tanpa data-testid, tambah attribute.
 * Idempotent — skip jika sudah ada data-testid.
 */
const fs = require('fs');
const path = require('path');
const ROOT = path.resolve(__dirname, '..');
const PAGES_DIR = path.join(ROOT, 'resources', 'js', 'Pages');

function walk(dir, out=[]) {
  for (const e of fs.readdirSync(dir, {withFileTypes:true})) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, out);
    else if (e.name.endsWith('.vue')) out.push(p);
  }
  return out;
}

const files = walk(PAGES_DIR);
let patched = 0, skipped = 0;

const rules = [
  { pattern: /(<button[^>]*)(@click="openCreate"[^>]*)(>)/g, attr: ' data-testid="btn-tambah"' },
  { pattern: /(<button[^>]*)(@click="openImport"[^>]*)(>)/g, attr: ' data-testid="btn-import"' },
  { pattern: /(<button[^>]*)(@click="exportAll"[^>]*)(>)/g, attr: ' data-testid="btn-export"' },
  { pattern: /(<input[^>]*)(placeholder="Cari[^"]*"[^>]*)(>)/g, attr: ' data-testid="input-search"' },
  { pattern: /(<button[^>]*)(@click="applySearch"[^>]*)(>)/g, attr: ' data-testid="btn-cari"' },
  { pattern: /(<button[^>]*)(@click="clearSearch"[^>]*)(>)/g, attr: ' data-testid="btn-clear-search"' },
  { pattern: /(<button[^>]*)(@click="applyFilters"[^>]*)(>)/g, attr: ' data-testid="btn-filter"' },
  { pattern: /(<button[^>]*)(@click="resetFilters"[^>]*)(>)/g, attr: ' data-testid="btn-reset-filter"' },
  { pattern: /(<select[^>]*)(v-model="perPage"[^>]*)(>)/g, attr: ' data-testid="select-per-page"' },
  { pattern: /(<input[^>]*)(v-model="selectAll"[^>]*)(type="checkbox"[^>]*)(>)/g, attr: ' data-testid="checkbox-select-all"' },
];

for (const file of files) {
  let content = fs.readFileSync(file, 'utf8');
  if (content.includes('data-testid')) {
    // sudah ada, tapi cek apakah masih ada button tanpa testid yang bisa dipatch
    // jangan skip full, tetap coba patch yang belum
  }
  let original = content;
  for (const r of rules) {
    content = content.replace(r.pattern, (match, p1, p2, p3) => {
      if (match.includes('data-testid')) return match;
      return p1 + p2 + r.attr + p3;
    });
  }
  // Tambah data-testid ke table jika belum
  if (!content.includes('data-testid="table-')) {
    content = content.replace(/<table/g, '<table data-testid="table-data"');
  }
  if (content !== original) {
    fs.writeFileSync(file, content, 'utf8');
    patched++;
    console.log(`patched: ${path.relative(ROOT, file)}`);
  } else {
    skipped++;
  }
}

console.log(`\nPatched ${patched} files, skipped ${skipped} (already have or no match)`);

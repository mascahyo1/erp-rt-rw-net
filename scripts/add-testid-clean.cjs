#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const ROOT = path.resolve(__dirname, '..');

const targets = [
  { file: 'resources/js/Pages/OperatorPerusahaan/Customer.vue', tests: [
    { find: /<button([^>]*?)@click="openCreate"([^>]*?)>/g, id: 'btn-tambah' },
    { find: /<button([^>]*?)@click="openImport"([^>]*?)>/g, id: 'btn-import' },
    { find: /<button([^>]*?)@click="exportAll"([^>]*?)>/g, id: 'btn-export' },
    { find: /<input([^>]*?)placeholder="Cari[^"]*"([^>]*?)>/g, id: 'input-search' },
    { find: /<button([^>]*?)@click="applySearch"([^>]*?)>/g, id: 'btn-cari' },
    { find: /<button([^>]*?)@click="clearSearch"([^>]*?)>/g, id: 'btn-clear-search' },
    { find: /<button([^>]*?)@click="applyFilters"([^>]*?)>/g, id: 'btn-filter' },
    { find: /<table([^>]*?)>/g, id: 'table-data' },
    { find: /<select([^>]*?)v-model="perPage"([^>]*?)>/g, id: 'select-per-page' },
  ]},
];

function addTestId(content, pattern, id) {
  return content.replace(pattern, (match) => {
    if (match.includes('data-testid')) return match;
    // Insert before final > or />
    if (match.endsWith('/>')) {
      return match.replace('/>', ` data-testid="${id}" />`);
    } else {
      return match.replace('>', ` data-testid="${id}">`);
    }
  });
}

// Generic: patch all CRUD pages that have table without testid
const allPages = [];
function walk(dir) {
  for (const e of fs.readdirSync(dir, {withFileTypes:true})) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p);
    else if (e.name.endsWith('.vue')) allPages.push(p);
  }
}
walk(path.join(ROOT, 'resources/js/Pages'));

let patchedFiles = 0;
for (const file of allPages) {
  let c = fs.readFileSync(file, 'utf8');
  let orig = c;
  // Only patch if file has table and no testid for table
  if (c.includes('<table') && !c.includes('data-testid="table-data"')) {
    c = c.replace(/<table/g, '<table data-testid="table-data"');
  }
  // Patch buttons with openCreate etc generically if not already
  const genericPatches = [
    { re: /<button([^>]*?)@click="openCreate"([^>]*?)>/g, id: 'btn-tambah' },
    { re: /<button([^>]*?)@click="openImport"([^>]*?)>/g, id: 'btn-import' },
    { re: /<button([^>]*?)@click="exportAll"([^>]*?)>/g, id: 'btn-export' },
    { re: /<input([^>]*?)placeholder="Cari[^"]*"([^>]*?)>/g, id: 'input-search' },
  ];
  for (const {re, id} of genericPatches) {
    c = c.replace(re, (m) => m.includes('data-testid') ? m : m.replace('>', ` data-testid="${id}">`).replace('/>', ` data-testid="${id}" />`));
  }
  // Special for checkbox selectAll
  c = c.replace(/<input([^>]*?)v-model="selectAll"([^>]*?)>/g, (m) => {
    if (m.includes('data-testid')) return m;
    // ensure space before data-testid and handle self-closing
    if (m.endsWith('/>')) return m.replace('/>', ` data-testid="checkbox-select-all" />`);
    return m.replace('>', ` data-testid="checkbox-select-all">`);
  });
  // Fix double spaces and ensure /> handling
  c = c.replace(/\s+data-testid/g, ' data-testid');
  // Fix malformed like data-testid="..."type -> add space
  c = c.replace(/data-testid="([^"]*)"([a-z])/g, 'data-testid="$1" $2');
  // Fix " / data-testid" -> " data-testid" ... />
  c = c.replace(/ \/ data-testid/g, ' data-testid');
  // Ensure self-closing has space before />
  c = c.replace(/data-testid="([^"]*)">/g, (m, id) => {
    // if original was self-closing, keep />
    return m;
  });

  if (c !== orig) {
    // Validate no " / data-testid" remains and no missing space
    if (c.includes(' / data-testid') || /data-testid="[^"]*"[a-z]/.test(c)) {
      console.error(`still malformed in ${path.relative(ROOT, file)}`);
      continue;
    }
    fs.writeFileSync(file, c, 'utf8');
    patchedFiles++;
    console.log(`patched ${path.relative(ROOT, file)}`);
  }
}
console.log(`Patched ${patchedFiles} files`);

#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const ROOT = path.resolve(__dirname, '..');
const files = [
  'resources/js/Pages/Customer/PaketTambah.vue',
  'resources/js/Pages/Customer/PembayaranTambah.vue',
  'resources/js/Pages/Customer/ProfilSaya.vue',
  'resources/js/Pages/Customer/PembayaranDetail.vue',
  'resources/js/Pages/Customer/DaftarPaketDetail.vue',
  'resources/js/Pages/Customer/PaketDetail.vue',
  'resources/js/Pages/Karyawan/ProfilSaya.vue',
  'resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue',
  'resources/js/Pages/OperatorPerusahaan/ProfilSaya.vue',
  'resources/js/Pages/OperatorSaas/PemetaanAdminPerusahaan.vue',
  'resources/js/Pages/OperatorSaas/ProfilSaya.vue',
  'resources/js/Pages/Customer/VerifikasiEmail.vue',
];

function addTestId(tag, html, id) {
  // html is full tag string like <form ...> or <button ...> or <input ...>
  if (html.includes('data-testid')) return html;
  // Handle self-closing: <input ... />
  if (html.endsWith('/>')) {
    return html.replace('/>', ` data-testid="${id}" />`);
  }
  // Normal: <form ...> or <button ...>
  return html.replace('>', ` data-testid="${id}">`);
}

let patched = 0;
for (const rel of files) {
  const file = path.join(ROOT, rel);
  if (!fs.existsSync(file)) continue;
  let c = fs.readFileSync(file, 'utf8');
  let orig = c;
  // Add to <form>
  c = c.replace(/<form[^>]*>/g, (m) => m.includes('data-testid') ? m : addTestId('form', m, 'form-main'));
  // Add to submit buttons
  c = c.replace(/<button[^>]*type="submit"[^>]*>/g, (m) => m.includes('data-testid') ? m : addTestId('button', m, 'btn-simpan'));
  // Add to inputs with placeholder
  c = c.replace(/<input[^>]*placeholder="[^"]*"[^>]*>/g, (m) => {
    if (m.includes('data-testid')) return m;
    const ph = m.match(/placeholder="([^"]+)"/);
    const id = ph ? 'input-' + ph[1].toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,20) : 'input-field';
    return addTestId('input', m, id);
  });
  if (c !== orig) {
    fs.writeFileSync(file, c, 'utf8');
    patched++;
    console.log(`patched ${rel}`);
  }
}
console.log(`Patched ${patched} files`);

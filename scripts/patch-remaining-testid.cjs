#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const ROOT = path.resolve(__dirname, '..');
const pages = [
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
  'resources/js/Pages/Landing/HubungiKami.vue',
  'resources/js/Pages/Landing/LupaPasswordOperatorSaaS.vue',
  'resources/js/Pages/Landing/LupaPasswordPelanggan.vue',
  'resources/js/Pages/Landing/LupaPasswordPerusahaan.vue',
];

let patched = 0;
for (const rel of pages) {
  const file = path.join(ROOT, rel);
  if (!fs.existsSync(file)) { console.log(`skip not found: ${rel}`); continue; }
  let c = fs.readFileSync(file, 'utf8');
  if (c.includes('data-testid="btn-simpan"') && c.includes('data-testid="form-')) { console.log(`already patched: ${rel}`); continue; }
  let orig = c;
  // Add data-testid to primary form submit buttons (type submit or @click save)
  c = c.replace(/(<button[^>]*type="submit"[^>]*)(>)/g, (m, a, b) => a.includes('data-testid') ? m : a + ' data-testid="btn-simpan"' + b);
  c = c.replace(/(<button[^>]*)(@click="[^"]*save[^"]*"[^>]*)(>)/gi, (m, a, b, cc) => m.includes('data-testid') ? m : a + b + ' data-testid="btn-simpan"' + cc);
  c = c.replace(/(<form[^>]*)(>)/g, (m, a, b) => a.includes('data-testid') ? m : a + ' data-testid="form-main"' + b);
  // Add to inputs that are required
  c = c.replace(/(<input[^>]*)(placeholder="[^"]*"[^>]*)(>)/g, (m, a, b, cc) => {
    if (m.includes('data-testid')) return m;
    // generate id from placeholder
    const ph = b.match(/placeholder="([^"]+)"/);
    const id = ph ? 'input-' + ph[1].toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'').slice(0,20) : 'input-field';
    return a + b + ` data-testid="${id}"` + cc;
  });
  if (c !== orig) {
    fs.writeFileSync(file, c, 'utf8');
    patched++;
    console.log(`patched: ${rel}`);
  }
}
console.log(`Patched ${patched} remaining files`);

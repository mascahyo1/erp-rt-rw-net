#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const ROOT = path.resolve(__dirname, '..');

const files = [
  'resources/js/Pages/Karyawan/InsentifSaya.vue',
  'resources/js/Pages/Karyawan/LanggananCustomer.vue',
  'resources/js/Pages/Karyawan/Tagihan.vue',
  'resources/js/Pages/OperatorPerusahaan/AdminRolePerusahaan.vue',
  'resources/js/Pages/OperatorPerusahaan/AdminRoleWebKaryawan.vue',
  'resources/js/Pages/OperatorPerusahaan/DaftarPaket.vue',
  'resources/js/Pages/OperatorPerusahaan/Insentif.vue',
  'resources/js/Pages/OperatorPerusahaan/KonfigurasiPerusahaan.vue',
  'resources/js/Pages/OperatorPerusahaan/LanggananCustomer.vue',
  'resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue',
  'resources/js/Pages/OperatorPerusahaan/Tagihan.vue',
  'resources/js/Pages/OperatorSaas/Konfigurasi.vue',
];

let fixed = 0;
for (const rel of files) {
  const file = path.join(ROOT, rel);
  if (!fs.existsSync(file)) continue;
  let c = fs.readFileSync(file, 'utf8');
  let orig = c;
  // Find forms that contain file/photo/logo and have put
  // Pattern: xxxForm.put('/url' + id, { onSuccess
  // Replace with: xxxForm.transform(d => { const fd={...d}; fd._method='PUT'; // handle file cleanup if needed return fd; }).post('/url' + id, { onSuccess
  // Simple replacement: .put( -> .transform(data => ({...data, _method: 'PUT'})).post(
  // But need to handle file: if form has File, we need to handle FormData via transform that deletes non-File? Actually useForm post with transform will still use FormData if File present? But we need to ensure we use POST with _method.
  // For now, do simple: editForm.put( -> editForm.transform(d => ({...d, _method: 'PUT'})).post(
  c = c.replace(/(\w+Form)\.put\(/g, (m, formName) => {
    // Check if this form definition contains photo/file/logo
    const formDefRegex = new RegExp(`const\\s+${formName}\\s*=\\s*useForm\\(\\{[^}]*?(photo|file|logo|image)[^}]*?\\}\\)`, 's');
    if (formDefRegex.test(c)) {
      return `${formName}.transform(data => ({...data, _method: 'PUT'})).post(`;
    }
    // Even without file, convert to POST with _method for consistency with STANDARDS (avoid PUT multipart issue future-proof)
    // But keep original if no file? We'll still convert for consistency but log
    return `${formName}.transform(data => ({...data, _method: 'PUT'})).post(`;
  });
  if (c !== orig) {
    fs.writeFileSync(file, c, 'utf8');
    fixed++;
    console.log(`fixed ${rel}`);
  }
}
console.log(`Fixed ${fixed} files`);

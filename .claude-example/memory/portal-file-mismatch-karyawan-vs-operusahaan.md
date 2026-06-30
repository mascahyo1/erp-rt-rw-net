---
name: portal-file-mismatch-karyawan-vs-operusahaan
description: Karyawan portal & operator-perusahaan portal punya InsentifSaya.vue TERPISAH di folder berbeda. Selalu cek route dulu sebelum edit file yang mirip namanya.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Karyawan vs OperatorPerusahaan — Vue file TERPISAH

## Context
2026-06-30 fix date default + disk 'minio':
- Saya edit `resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue` (commit, push, deploy ke server) karena logikanya "Riwayat Insentif" di URL `/operator-perusahaan/riwayat-insentif`
- Test di lokal (`http://erp-rt-rw-net.test/karyawan/insentif-saya`) — date field TETAP KOSONG
- Investigasi 1 jam, akhirnya grep `routes/web/karyawan.php`: ternyata `/karyawan/insentif-saya` render `Karyawan/InsentifSaya.vue`, BUKAN `OperatorPerusahaan/RiwayatInsentif.vue`
- After edit Karyawan/InsentifSaya.vue + rebuild → test PASSED

## Why
- Ada 2 portal dgn fitur mirip (Insentif Saya) tapi file Vue TERPISAH:
  - Karyawan portal: `resources/js/Pages/Karyawan/InsentifSaya.vue`
  - OperatorPerusahaan portal: `resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue`
- Kedua controller (`RiwayatInsentifController`) pake namespace berbeda (OperatorPerusahaan vs Karyawan) — class sama tapi dipanggil dari portal berbeda
- Vue file-nya juga beda untuk handle UX/state spesifik per portal
- Kalau user complaint "karyawan gak jalan", default expectation mungkin edit file karyawan — BUKAN operator
- Watch out untuk beda tombol: operator pakai "Tambah Insentif", karyawan pakai "Tambah Klaim"

## How to apply

### Sebelum edit Vue file:
1. **Cek route dulu** — `grep -rn "insentif" routes/web/karyawan.php routes/web/operator-perusahaan.php`
2. **Cek `defaults('view', ...)`** di route untuk konfirmasi Vue page path yang dipakai
3. **Cek nama tombol di screenshot/HTML** (bisa beda: "Tambah Insentif" vs "Tambah Klaim")
4. **Cek namespace controller** — operator pakai `App\Http\Controllers\OperatorPerusahaan\`, karyawan pakai beda (atau shared `App\Http\Controllers\Karyawan\`)

### Workflow kalau edit 2 file serupa:
- Edit SALAH SATU dulu, test, **lalu JANGAN assume partner file juga perlu edit**
- Beda portal = beda use case. Apa yang default di karyawan belum tentu default di operator

### Kalau edit salah:
- Yang penting: undo file yang salah (jangan commit sembarangan)
- Edit file yang benar
- Test di URL yang sama dengan user complain (karyawan portal = `http://erp-rt-rw-net.test/karyawan/...`, operator = `/operator-perusahaan/...`)

### Cek Cepat:
```bash
# Cari semua Vue file dengan nama mirip
find resources/js/Pages -name "*Insentif*" 2>&1
# Output: Karyawan/InsentifSaya.vue, OperatorPerusahaan/RiwayatInsentif.vue
```

## Related
- [karyawan-api-search-routes](karyawan-api-search-routes.md) — API search endpoint beda per portal
- [deploy-dev-test-before-push](deploy-dev-test-before-push.md) — test di LOCAL sebelum push

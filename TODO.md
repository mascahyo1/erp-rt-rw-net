# TODO - ERP RT/RW Net (autoeng backlog)

## Aktif

- [ ] erp-1: Dev 500 MissingAppKeyException - jalankan key:generate untuk APP_KEY, pastikan https://dev.jmpgroup.id/ 200. [risiko:rendah]
( kosong - belum ada tugas untuk erp. Tambahkan item - [ ] erp-NN: deskripsi [risiko:rendah|sedang|tinggi] untuk memicu loop. )

## Riwayat
- 2026-10-12: autoeng erp terdaftar; loop idle sampai ada item baru.
- [ ] erp-2: Migrasi semua controller portal ke pola Request/Service/Resource (skill laravel-portal-layers): controller tipis; Requests/Services/Resources per portal-halaman-aksi; model diet; tanpa ubah behavior. Gate: pint + test hijau. [risiko:tinggi]
- [ ] erp-3: Restruktur controller per portal seperti BIS (route/web dan Pages sudah terfolder: customer/karyawan/landing/operator-perusahaan/operator-saas). Pindahkan controller flat (AdminCompanyController, CompanyController, CrudController, Role*Controller, FileProxyController, dsb) ke folder portal (Saas/Perusahaan/Karyawan/Customer) + namespace; update render(); 1 portal 1 prefix, 1 controller 1 halaman per portal. Tanpa ubah behavior. Gate: php artisan test hijau. [risiko:tinggi]

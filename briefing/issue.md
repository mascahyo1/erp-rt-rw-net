issue 1
filter, datatable,modal dan alert konfirmasi banyak yang tidak konsisten tampilannya sebaiknya dijadikan komponen aja biar konsisten
contoh terbaiknya ini http://erp-rt-rw-net.test/operator-saas/admin-saas
issue 2 
crud kayak http://erp-rt-rw-net.test/operator-perusahaan/langganan-customer input pelanggan dan paket 
http://erp-rt-rw-net.test/operator-perusahaan/tagihan inputan customer internetnya
http://erp-rt-rw-net.test/operator-perusahaan/riwayat-insentif riwayat insentif nama insentif / id insentif sama id invoice
riwayat pembayaran http://erp-rt-rw-net.test/operator-perusahaan/riwayat-pembayaran id invoice
itu harusnya select option searchable infinite scroll ajax.
issue 3
saya login operator saas
saya login operator perusahaan
http://erp-rt-rw-net.test/operator-saas/role-perusahaan kok 403
saya logout operator perusahaan bisa
ini kok gak stabil stabil dari tadi kenapa?kendalanya apa? pastikan stabil mau semua login sekaligus aman


issue 4
http://erp-rt-rw-net.test/operator-perusahaan/langganan-customer
perasaan saya sudah minta untuk crud langganan input pelanggan menggunakan select option yang searchable infinite sroll ajax
issue 5
http://erp-rt-rw-net.test/operator-perusahaan/tagihan crud tambah tagihan input customer internet id menggunakan select option yang searchable infinite sroll ajax
issue 6
http://erp-rt-rw-net.test/operator-perusahaan/riwayat-insentif crud riwayat insentif input insentif dan invoice  menggunakan select option yang searchable infinite sroll ajax
issue 7
http://erp-rt-rw-net.test/operator-perusahaan/riwayat-pembayaran crud riwayat pembayaran id invoice menggunakan select option yang searchable infinite sroll ajax


issue 
laravel dusk susah membuat video jadi untuk testing menggunakan node js dan playwright saja. laravel dusk kurang stabil buat menjalankan test
refactor semua test dusk ke playwright node js. jangan hapus file lama
strukturnya gini untuk kode file
./tests/Browser/Playwright/Feature/OperatorSaas/*
./tests/Browser/Playwright/Feature/OperatorPerusahaan/*
./tests/Browser/Playwright/Feature/Karyawan/*
./tests/Browser/Playwright/Feature/Pelanggan/*
untuk output test begitu
./tests/Browser/Playwright/result/OperatorSaas/*
./tests/Browser/Playwright/result/OperatorPerusahaan/*
./tests/Browser/Playwright/result/Karyawan/*
./tests/Browser/Playwright/result/Pelanggan/*
contoh lagi
./tests/Browser/Playwright/result/OperatorPerusahaan/DaftarPaket/TestCRUD/*
./tests/Browser/Playwright/result/OperatorPerusahaan/DaftarPaket/TestPermission/*

semua test dibuat seperti itu

baca2 dulu file di ./dokumentasi/* dan di ./tests\Browser\deprecatedoldFeature\*

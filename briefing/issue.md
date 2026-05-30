tugas 1
http://erp-rt-rw-net.test/operator-perusahaan/perusahaan-saya crud perusahaan saya ada dokumentasi tidak disini dokumentasi\operator-perusahaan seharusnya ada?
tambahkan test case
kalau user punya hak akses perusahaan-saya.detail sidebar muncul terus di dropdown navbar muncul juga perusahaan saya
app\Enums\Permissions.php
perusahaan-saya.list take out saja
terus kalau punya akses edit juga tombol edit muncul
jadi testing detail ubahnya, dark / light mode, responsif


crud pelanggan
dokumentasi\operator-perusahaan\halaman-customer.md
inputan foto profil dibawahnya ada ket max 2mb, ekstensi jpg, jpeg, png, webp. validasi mime juga
inputan ktp dan kk dibawahnya ada ket max 2mb ekstensi jpg, jpeg, png, webp, pdf mime juga
auto dicompress biar hemat space kalau foto jadi webp max res 1980*1980 maintain aspect ratio.
coba kalau pdf dicompress juga. 
update fe,be,dokumentasi,test case
untuk konfigurasi sebaiknya gini ya. dari tabel saas_configs 
untuk where key = default_upload_max_width_and_height_image_in_kb -> value kasih 1920
untuk file upload default_upload_max_file_size_in_kb -> value kasih 2mb
autocompress file upload key = default_auto_compress_file_upload -> value 0/1
tambahkan itu di 
database\seeders\DemoSeeder.php
database\seeders\ProductionSeeder.php


http://erp-rt-rw-net.test/operator-perusahaan/tagihan
cetak tagihan

tugas 2
tagihan 
insentif
tugas 3
riwayatt insentif
riwayat pembayaran
tugas 4
admin perusahaan
role perushaaan
admin role perusahaan
tugas 5
karyawan
role web karyawan
admin role web karyawan
konfig perusahaan


riwayat insentif
app\Enums\Permissions.php line 124-130 tambahkan export import dan line 308 df 314
dokumentasi\operator-perusahaan\halaman-riwayat-insentif.md
saya mau ada fitur import export
testing semua mulai dari crud, tampilan dark / light mode, tampilan responsif update test case dan dokumentasinya
sebaiknya kolom datatable export dan datatable webnya
kode invoice
nominal invoice
status invoice
tgl jatuh tempo
nama paket
kode pelanggan
nama pelanggan
email pelanggan
no telp pelanggan (gabung phone_country_code dan phone number)

filter hanya berjalan ketika diklik tombol filter bukan on change biar gak mubasir query
filter tambahkan
dari tgl jatuh tempo
sampai tgl jatuh tempo
status
nama paket(sebaiknya select option ajax dropdown  infinite scroll bisa searching seperti contoh lain mungkin ini http://erp-rt-rw-net.test/operator-perusahaan/tagihan yg bagian filter paket)
pelanggan(sebaiknya select option ajax dropdown  infinite scroll bisa searching)
kode invoice(sebaiknya select option ajax dropdown  infinite scroll bisa searching, chaining dia memperhatikan dari tgl jatuh tempo, sampai tgl jatuh tempo, nama paket, nama pelanggan)
checklist per row tidak bisa diklik


input create / edit fieldnya
kode invoice 
nama insentif
tgl insentif
amount(auto hitung tapi bisa diubah)
diajukan untuk (submitted_by_type,submitted_by_id)
alasan pengajuan
bukti pengajuan

detail fieldnya
kode invoice 
nama insentif
tgl insentif
amount
diajukan untuk (submitted_by_type,submitted_by_id)
alasan pengajuan
bukti pengajuan
status
ditinjau oleh

aksinya review per row
review sekaligus dari checklist

itu buka modal baru inputannya
review_status
review_reason(wajib diisi kalau ditolak)
review_attachment(ga wajib, saya cek ini timestamp seharusnya string ubah aja migrasinya)

kalau mau review user harus memiliki permission riwayat-insentif.persetujuan


edit dan review hanya bisa dilakukan ketika status pending


kalau disetujui / ditolak keisi otomatis ini
reviewed_by_type 
reviewed_by_id 




isu riwayat pembayaran
http://erp-rt-rw-net.test/operator-perusahaan/riwayat-pembayaran
buat dulu enumsnya 
provider internal payment method opsinya nanti tunai, transfer manual keduanya ini nanti wajib isi bukti bayar proof_file
app\Enums\Permissions.php ini tambahkan permission riwayat pembayaran import excel, export excel, persetujuan. ketika import excel ada download template biar user paham
crud ada filter
provider, metode pembayaran, dari tgl dibuat sampai tgl dibuat, filter triggernya bukan on change tetapi tombol filter di card biar hemat query
tambahkan seeder demonya
update dokumentasi dan testingnya
jalankan testing sampai semuanya passed
testing meliputi crud, import export, bulk action, tampilan responsif, tampilan light dan dark mode

ada aksi review sekaligus juga biar enak
ada aksi review inputannya status dan status_reason.
jika statusnya masih pending bisa direview untuk rejected atau jadi paid jika user memiliki permission itu
jadi modal edit dan review beda. mereka modal berbeda



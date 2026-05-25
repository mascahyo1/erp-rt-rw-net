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
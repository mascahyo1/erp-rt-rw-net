dari halaman http://erp-rt-rw-net.test/operator-perusahaan/tagihan ada tombol generate ketika 
diklik muncul modal
inputan pertama adalah billing cycle tagihan opsinya harian, mingguan, bulanan, tahunan
jika tahunan tahun, jatuh tempo
jika bulanan inputan yang muncul tahun, bulan, jatuh tempo
jika mingguan usage_start_date, usage_end_date, jatuh tempo (jaraknya harus 1 minggu validasinya)
jika harian usage_start_date, usage_end_date, 
jatuh tempo (jika jarak > 1 hari, misal 2 hari ya generate invoicenya 2x gitu)
kalau gini gimana lebih bagus dan keren kan? jangan ngoding dulu ya btw

NET SEJAHTERA ABADI = NSA
MAJU MUNDUR JAYA = MMJ
INV-NSA-(BILLING CYCLE Y/M/W/D)-PERIOD-(TIMESTAMP HINGGA MILIDETIK)-(6 RANDOM HURUF BESAR)-(6 RANDOM ANGKA)

INV-NSA-W-2026W24-1717838401789-BCDEFG-345678 AMBIL DARI usage_start_date aja yang weekly

gak ada kode perusahaan
langsung aja
INV-(BILLING CYCLE Y/M/W/D)-PERIOD-(TIMESTAMP HINGGA MILIDETIK)-(6 RANDOM HURUF BESAR)-(6 RANDOM ANGKA)

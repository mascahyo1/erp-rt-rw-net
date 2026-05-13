<?php

namespace Database\Seeders;

use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\SaasConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        AdminSaas::query()->insert([
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin Demo',
                'email' => 'superadmin@demo.test',
                'phone_country_code' => '+62',
                'phone_number' => '81111111111',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Admin Operator Demo',
                'email' => 'admin@demo.test',
                'phone_country_code' => '+62',
                'phone_number' => '82222222222',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $companies = [
            ['id' => Str::uuid(), 'name' => 'PT Net Sejahtera Abadi', 'email' => 'info@netsejahtera.com', 'phone_country_code' => '+62', 'phone_number' => '82112345678', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'CV Digital Media Nusantara', 'email' => 'admin@digitalmedia.id', 'phone_country_code' => '+62', 'phone_number' => '82187654321', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'UD Net Mandiri Global', 'email' => 'support@netmandiri.com', 'phone_country_code' => '+62', 'phone_number' => '82211223344', 'is_active' => false],
            ['id' => Str::uuid(), 'name' => 'PT Jaringan Prima', 'email' => 'halo@jaringanprima.com', 'phone_country_code' => '+62', 'phone_number' => '82244332211', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'CV Angkasa Netindo', 'email' => 'info@angkanet.id', 'phone_country_code' => '+62', 'phone_number' => '82255667788', 'is_active' => true],
        ];

        foreach ($companies as $company) {
            $company['created_at'] = now();
            $company['updated_at'] = now();
            Company::query()->insert($company);
        }

        SaasConfig::query()->insert([
            [
                'id' => Str::uuid(),
                'key' => 'contact.phone',
                'type' => 'text',
                'value' => '+62 812-3456-7890',
                'descripton' => 'Nomor telepon kontak utama halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.email',
                'type' => 'text',
                'value' => 'support@rtrwnet.id',
                'descripton' => 'Email kontak utama halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.address',
                'type' => 'text',
                'value' => 'Jl. Teknologi No. 10, Jakarta Selatan, DKI Jakarta 12950',
                'descripton' => 'Alamat kantor halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.hours_weekday',
                'type' => 'text',
                'value' => 'Senin — Jumat: 08:00 — 20:00 WIB',
                'descripton' => 'Jam operasional hari kerja',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.hours_saturday',
                'type' => 'text',
                'value' => 'Sabtu: 09:00 — 15:00 WIB',
                'descripton' => 'Jam operasional hari Sabtu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'company.email',
                'type' => 'text',
                'value' => 'support@erprtrw.net',
                'descripton' => 'Email perusahaan untuk halaman error',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'company.phone',
                'type' => 'text',
                'value' => '+62 851-2345-6789',
                'descripton' => 'Telepon perusahaan untuk halaman error',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'terms',
                'type' => 'text',
                'value' => json_encode($this->getTermsSections()),
                'descripton' => 'Syarat dan Ketentuan (JSON sections)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'privacy',
                'type' => 'text',
                'value' => json_encode($this->getPrivacySections()),
                'descripton' => 'Kebijakan Privasi (JSON sections)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function getTermsSections(): array
    {
        return [
            ['title' => 'Penerimaan Persyaratan', 'body' => '<p class="text-sm leading-relaxed">Dengan mengakses atau menggunakan layanan RT/RW Net ERP ("Layanan"), Anda setuju untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak setuju dengan bagian mana pun dari syarat dan ketentuan ini, Anda tidak boleh menggunakan Layanan kami.</p>'],
            ['title' => 'Definisi', 'body' => '<ul class="list-disc list-inside text-sm leading-relaxed space-y-1"><li><strong>"Platform"</strong> adalah aplikasi ERP RT/RW Net berbasis web.</li><li><strong>"Tenant"</strong> adalah perusahaan atau entitas yang menggunakan Platform.</li><li><strong>"Pengguna"</strong> adalah individu yang memiliki akun dan mengakses Platform.</li><li><strong>"Pelanggan"</strong> adalah pelanggan akhir dari Tenant (pelanggan RT/RW Net).</li></ul>'],
            ['title' => 'Akun dan Keamanan', 'body' => '<p class="text-sm leading-relaxed mb-2">Anda bertanggung jawab untuk menjaga kerahasiaan informasi login akun Anda dan untuk semua aktivitas yang terjadi di bawah akun Anda. Anda setuju untuk segera memberi tahu kami tentang penggunaan tidak sah atas akun Anda atau pelanggaran keamanan lainnya.</p><p class="text-sm leading-relaxed">Platform tidak akan bertanggung jawab atas kerugian apa pun yang timbul dari penggunaan akun Anda yang tidak sah.</p>'],
            ['title' => 'Penggunaan Layanan', 'body' => '<p class="text-sm leading-relaxed">Anda setuju untuk menggunakan Layanan hanya untuk tujuan yang sah dan sesuai dengan ketentuan yang berlaku. Anda tidak diperbolehkan menyalahgunakan Layanan untuk aktivitas ilegal, penipuan, atau yang merugikan pihak lain.</p>'],
            ['title' => 'Harga dan Pembayaran', 'body' => '<p class="text-sm leading-relaxed">Biaya berlangganan Layanan ditentukan berdasarkan paket yang dipilih. Semua pembayaran dilakukan di muka dan tidak dapat dikembalikan kecuali ditentukan lain. Platform berhak mengubah harga dengan pemberitahuan 30 hari sebelumnya.</p>'],
            ['title' => 'Kekayaan Intelektual', 'body' => '<p class="text-sm leading-relaxed">Semua konten, kode, desain, dan materi dalam Platform adalah milik Platform atau pemberi lisensinya dan dilindungi oleh hukum kekayaan intelektual. Anda tidak diperbolehkan menyalin, memodifikasi, atau mendistribusikan konten Platform tanpa izin.</p>'],
            ['title' => 'Batasan Tanggung Jawab', 'body' => '<p class="text-sm leading-relaxed">Platform disediakan "apa adanya" tanpa jaminan apa pun, tersurat maupun tersirat. Kami tidak bertanggung jawab atas kerugian langsung, tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan Layanan.</p>'],
            ['title' => 'Perubahan Ketentuan', 'body' => '<p class="text-sm leading-relaxed">Kami berhak mengubah Syarat dan Ketentuan ini kapan saja. Perubahan akan diumumkan melalui Platform atau email. Penggunaan Layanan setelah perubahan berarti Anda menerima ketentuan yang diperbarui.</p>'],
            ['title' => 'Hukum yang Berlaku', 'body' => '<p class="text-sm leading-relaxed">Syarat dan Ketentuan ini diatur oleh hukum Republik Indonesia. Setiap sengketa akan diselesaikan di pengadilan negeri yang berwenang di wilayah Jakarta Selatan.</p>'],
            ['title' => 'Kontak', 'body' => '<p class="text-sm leading-relaxed">Jika Anda memiliki pertanyaan tentang Syarat dan Ketentuan ini, silakan hubungi kami di <a href="mailto:legal@rtrwnet.id" class="text-sky-600 dark:text-sky-400 underline">legal@rtrwnet.id</a>.</p>'],
        ];
    }

    private function getPrivacySections(): array
    {
        return [
            ['title' => 'Informasi yang Kami Kumpulkan', 'body' => '<p class="text-sm leading-relaxed mb-2">Kami mengumpulkan informasi berikut saat Anda menggunakan Platform:</p><ul class="list-disc list-inside text-sm leading-relaxed space-y-1"><li><strong>Informasi Akun:</strong> Nama, email, nomor telepon, alamat.</li><li><strong>Informasi Perusahaan:</strong> Nama perusahaan, logo, alamat bisnis.</li><li><strong>Data Transaksi:</strong> Data pelanggan, tagihan, pembayaran, insentif.</li><li><strong>Data Log:</strong> Alamat IP, jenis browser, halaman yang diakses, waktu akses.</li></ul>'],
            ['title' => 'Penggunaan Informasi', 'body' => '<p class="text-sm leading-relaxed mb-2">Informasi yang kami kumpulkan digunakan untuk:</p><ul class="list-disc list-inside text-sm leading-relaxed space-y-1"><li>Menyediakan dan memelihara Layanan.</li><li>Memproses data bisnis Anda (pelanggan, tagihan, pembayaran).</li><li>Mengirim pemberitahuan terkait akun dan layanan.</li><li>Meningkatkan kualitas Layanan berdasarkan analisis penggunaan.</li><li>Mencegah aktivitas penipuan dan menjaga keamanan Platform.</li></ul>'],
            ['title' => 'Penyimpanan Data', 'body' => '<p class="text-sm leading-relaxed">Data Anda disimpan di server yang aman dengan enkripsi. Kami menerapkan langkah-langkah keamanan teknis dan organisasi untuk melindungi data Anda dari akses, perubahan, pengungkapan, atau penghancuran yang tidak sah. File yang diunggah (seperti bukti transfer) disimpan di penyimpanan objek S3-compatible dengan signed URL.</p>'],
            ['title' => 'Berbagi Data', 'body' => '<p class="text-sm leading-relaxed">Kami tidak menjual, memperdagangkan, atau mentransfer data pribadi Anda kepada pihak ketiga di luar yang diperlukan untuk menyediakan Layanan. Ini tidak termasuk pihak ketiga tepercaya yang membantu kami dalam mengoperasikan Platform, selama pihak tersebut setuju untuk menjaga kerahasiaan data.</p>'],
            ['title' => 'Hak Pengguna', 'body' => '<p class="text-sm leading-relaxed mb-2">Anda memiliki hak untuk:</p><ul class="list-disc list-inside text-sm leading-relaxed space-y-1"><li>Mengakses data pribadi Anda yang kami simpan.</li><li>Memperbaiki data yang tidak akurat.</li><li>Menghapus data Anda (subject to legal retention requirements).</li><li>Menarik persetujuan pemrosesan data kapan saja.</li></ul>'],
            ['title' => 'Cookie', 'body' => '<p class="text-sm leading-relaxed">Kami menggunakan cookie esensial untuk menjaga sesi login dan preferensi tema (dark/light mode). Kami tidak menggunakan cookie pelacakan pihak ketiga. Anda dapat menonaktifkan cookie di browser Anda, tetapi beberapa fitur Platform mungkin tidak berfungsi dengan baik.</p>'],
            ['title' => 'Perubahan Kebijakan', 'body' => '<p class="text-sm leading-relaxed">Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan akan diumumkan melalui email atau pemberitahuan di Platform. Penggunaan Layanan setelah perubahan berarti Anda menerima kebijakan yang diperbarui.</p>'],
            ['title' => 'Kontak', 'body' => '<p class="text-sm leading-relaxed">Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi kami di <a href="mailto:privacy@rtrwnet.id" class="text-sky-600 dark:text-sky-400 underline">privacy@rtrwnet.id</a>.</p>'],
        ];
    }
}

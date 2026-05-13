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
                'key' => 'contact.working_schedule',
                'type' => 'text',
                'value' => "Senin — Jumat: 08:00 — 20:00 WIB\nSabtu: 09:00 — 15:00 WIB",
                'descripton' => 'Jadwal operasional lengkap (bisa termasuk hari libur, akhir pekan, 24/7)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.whatsapp',
                'type' => 'text',
                'value' => '+62 812-3456-7890',
                'descripton' => 'Nomor WhatsApp kontak',
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
                'key' => 'contact.email_terms',
                'type' => 'text',
                'value' => 'legal@rtrwnet.id',
                'descripton' => 'Email kontak halaman Syarat & Ketentuan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.email_privacy',
                'type' => 'text',
                'value' => 'privacy@rtrwnet.id',
                'descripton' => 'Email kontak halaman Kebijakan Privasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

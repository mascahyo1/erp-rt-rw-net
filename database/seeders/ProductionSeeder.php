<?php

namespace Database\Seeders;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\SaasConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        AdminSaas::query()->insert([
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'email' => 'superadmin@rtrwnet.id',
                'phone_country_code' => '+62',
                'phone_number' => '81111111111',
                'is_active' => true,
                'password' => bcrypt('P@ssw0rd!2026'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Admin Operator',
                'email' => 'admin@rtrwnet.id',
                'phone_country_code' => '+62',
                'phone_number' => '82222222222',
                'is_active' => true,
                'password' => bcrypt('P@ssw0rd!2026'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $companyId = Str::uuid();

        Company::query()->insert([
            'id' => $companyId,
            'name' => 'PT Net Sejahtera',
            'email' => 'info@netsejahtera.id',
            'phone_country_code' => '+62',
            'phone_number' => '82112345678',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            // File upload settings
            [
                'id' => Str::uuid(),
                'key' => 'default_upload_max_width_and_height_image',
                'type' => 'number',
                'value' => '1920',
                'descripton' => 'Max width/height for image upload in pixels',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'default_upload_max_file_size_in_kb',
                'type' => 'number',
                'value' => '2048',
                'descripton' => 'Max file size for upload in KB (2048 = 2MB)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'default_auto_compress_file_upload',
                'type' => 'boolean',
                'value' => 'true',
                'descripton' => 'Auto compress image uploads to WebP (true=enabled, false=disabled)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        AdminCompany::query()->insert([
            [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'name' => 'Admin Perusahaan',
                'email' => 'admin@netsejahtera.id',
                'phone_country_code' => '+62',
                'phone_number' => '82112345678',
                'is_active' => true,
                'password' => bcrypt('P@ssw0rd!2026'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->call(PermissionSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\CompanyConfig;
use App\Models\Customer;
use App\Models\Employee;
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
            ['id' => Str::uuid(), 'name' => 'PT Net Sejahtera Abadi', 'email' => 'info@netsejahtera.com', 'phone_country_code' => '+62', 'phone_number' => '82112345678', 'address' => 'Jl. Raya Barat No. 100, Jakarta Barat 11610', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'CV Digital Media Nusantara', 'email' => 'admin@digitalmedia.id', 'phone_country_code' => '+62', 'phone_number' => '82187654321', 'address' => 'Jl. Merdeka No. 50, Bandung 40111', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'UD Net Mandiri Global', 'email' => 'support@netmandiri.com', 'phone_country_code' => '+62', 'phone_number' => '82211223344', 'address' => 'Jl. Sudirman No. 25, Surabaya 60271', 'is_active' => false],
            ['id' => Str::uuid(), 'name' => 'PT Jaringan Prima', 'email' => 'halo@jaringanprima.com', 'phone_country_code' => '+62', 'phone_number' => '82244332211', 'address' => 'Jl. Asia Afrika No. 88, Bandung 40112', 'is_active' => true],
            ['id' => Str::uuid(), 'name' => 'CV Angkasa Netindo', 'email' => 'info@angkanet.id', 'phone_country_code' => '+62', 'phone_number' => '82255667788', 'address' => 'Jl. Gatot Subroto No. 45, Jakarta Selatan 12990', 'is_active' => true],
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
                'description' => 'Nomor telepon kontak utama halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.email',
                'type' => 'text',
                'value' => 'support@rtrwnet.id',
                'description' => 'Email kontak utama halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.address',
                'type' => 'text',
                'value' => 'Jl. Teknologi No. 10, Jakarta Selatan, DKI Jakarta 12950',
                'description' => 'Alamat kantor halaman Hubungi Kami',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.working_schedule',
                'type' => 'text',
                'value' => "Senin â€” Jumat: 08:00 â€” 20:00 WIB\nSabtu: 09:00 â€” 15:00 WIB",
                'description' => 'Jadwal operasional lengkap (bisa termasuk hari libur, akhir pekan, 24/7)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.whatsapp',
                'type' => 'text',
                'value' => '+62 812-3456-7890',
                'description' => 'Nomor WhatsApp kontak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'company.email',
                'type' => 'text',
                'value' => 'support@erprtrw.net',
                'description' => 'Email perusahaan untuk halaman error',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'company.phone',
                'type' => 'text',
                'value' => '+62 851-2345-6789',
                'description' => 'Telepon perusahaan untuk halaman error',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.email_terms',
                'type' => 'text',
                'value' => 'legal@rtrwnet.id',
                'description' => 'Email kontak halaman Syarat & Ketentuan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'contact.email_privacy',
                'type' => 'text',
                'value' => 'privacy@rtrwnet.id',
                'description' => 'Email kontak halaman Kebijakan Privasi',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // File upload settings
            [
                'id' => Str::uuid(),
                'key' => 'default_upload_max_width_and_height_image',
                'type' => 'number',
                'value' => '1920',
                'description' => 'Max width/height for image upload in pixels',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'default_upload_max_file_size_in_kb',
                'type' => 'number',
                'value' => '2048',
                'description' => 'Max file size for upload in KB (2048 = 2MB)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'key' => 'default_auto_compress_file_upload',
                'type' => 'boolean',
                'value' => 'true',
                'description' => 'Auto compress image uploads to WebP (true=enabled, false=disabled)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Company-scoped configs (one set per demo company)
        $company1Id = $companies[0]['id'];
        $company2Id = $companies[1]['id'];
        $company3Id = $companies[2]['id'];

        foreach ([$company1Id, $company2Id, $company3Id] as $cid) {
            CompanyConfig::query()->insert([
                [
                    'id' => Str::uuid(),
                    'company_id' => $cid,
                    'key' => 'company.tagline',
                    'type' => 'text',
                    'value' => 'ISP terpercaya dengan jangkauan luas di kota Anda',
                    'description' => 'Tagline utama yang ditampilkan di dashboard',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Str::uuid(),
                    'company_id' => $cid,
                    'key' => 'company.max_devices',
                    'type' => 'number',
                    'value' => '5',
                    'description' => 'Maksimum device yang boleh terhubung per pelanggan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Str::uuid(),
                    'company_id' => $cid,
                    'key' => 'company.is_active',
                    'type' => 'boolean',
                    'value' => 'true',
                    'description' => 'Status aktif perusahaan (true = beroperasi, false = maintenance)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => Str::uuid(),
                    'company_id' => $cid,
                    'key' => 'company.terms_pdf',
                    'type' => 'file',
                    'value' => 'storage/company-configs/' . $cid . '/terms.pdf',
                    'description' => 'Path file PDF Syarat & Ketentuan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        $company4Id = $companies[3]['id'];
        $company5Id = $companies[4]['id'];

        AdminCompany::query()->insert([
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'name' => 'Admin Net Sejahtera',
                'email' => 'admin@netsejahtera.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345670',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'name' => 'Admin Digital Media',
                'email' => 'admin@digitalmedia.id',
                'phone_country_code' => '+62',
                'phone_number' => '82187654320',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company3Id,
                'name' => 'Admin Net Mandiri',
                'email' => 'admin@netmandiri.com',
                'phone_country_code' => '+62',
                'phone_number' => '82211223340',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'name' => 'Admin Jaringan Prima',
                'email' => 'admin@jaringanprima.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332210',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'name' => 'Admin Angkasa Netindo',
                'email' => 'admin@angkanet.id',
                'phone_country_code' => '+62',
                'phone_number' => '82255667780',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Employee::query()->insert([
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'KRY001',
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad@netsejahtera.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345671',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'KRY002',
                'name' => 'Siti Nuraini',
                'email' => 'siti@netsejahtera.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345672',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'KRY003',
                'name' => 'Budi Santoso',
                'email' => 'budi@netsejahtera.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345673',
                'is_active' => false,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'code' => 'KRY004',
                'name' => 'Dewi Lestari',
                'email' => 'dewi@digitalmedia.id',
                'phone_country_code' => '+62',
                'phone_number' => '82187654321',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'code' => 'KRY005',
                'name' => 'Rudi Hermawan',
                'email' => 'rudi@digitalmedia.id',
                'phone_country_code' => '+62',
                'phone_number' => '82187654322',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'code' => 'KRY006',
                'name' => 'Hendra Gunawan',
                'email' => 'hendra@jaringanprima.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332211',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'code' => 'KRY007',
                'name' => 'Ratna Sari',
                'email' => 'ratna@jaringanprima.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332212',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'code' => 'KRY008',
                'name' => 'Andi Prasetyo',
                'email' => 'andi@angkanet.id',
                'phone_country_code' => '+62',
                'phone_number' => '82255667781',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'code' => 'KRY009',
                'name' => 'Maya Indah',
                'email' => 'maya@angkanet.id',
                'phone_country_code' => '+62',
                'phone_number' => '82255667782',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Customer::query()->insert([
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'CUST-001',
                'name' => 'Pak Sugeng',
                'email' => 'sugeng@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345681',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Melati No. 12, RT 03 RW 05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'CUST-002',
                'name' => 'Bu Rini',
                'email' => 'rini@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345682',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Anggrek No. 5, RT 02 RW 05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'CUST-003',
                'name' => 'Pak Herman',
                'email' => 'herman@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345683',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Mawar No. 8, RT 01 RW 05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company1Id,
                'code' => 'CUST-004',
                'name' => 'Mbak Dewi',
                'email' => 'dewi.w@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82112345684',
                'is_active' => false,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Kenanga No. 3, RT 04 RW 05',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'code' => 'CUST-005',
                'name' => 'Pak Slamet',
                'email' => 'slamet@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82187654331',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Cendana No. 10, RT 02 RW 03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'code' => 'CUST-006',
                'name' => 'Bu Tuti',
                'email' => 'tuti@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82187654332',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Cempaka No. 7, RT 01 RW 03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company2Id,
                'code' => 'CUST-007',
                'name' => 'Pak Joko',
                'email' => 'joko@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82187654333',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Dahlia No. 15, RT 03 RW 03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'code' => 'CUST-008',
                'name' => 'Pak Wahyu',
                'email' => 'wahyu@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332221',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Flamboyan No. 2, RT 05 RW 02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'code' => 'CUST-009',
                'name' => 'Bu Ani',
                'email' => 'ani@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332222',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Asoka No. 11, RT 05 RW 02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company4Id,
                'code' => 'CUST-010',
                'name' => 'Pak Dodi',
                'email' => 'dodi@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82244332223',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Teratai No. 6, RT 06 RW 02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'code' => 'CUST-011',
                'name' => 'Pak Ujang',
                'email' => 'ujang@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82255667791',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Bougenville No. 9, RT 01 RW 07',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'code' => 'CUST-012',
                'name' => 'Bu Lilis',
                'email' => 'lilis@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82255667792',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Nusa Indah No. 4, RT 02 RW 07',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'company_id' => $company5Id,
                'code' => 'CUST-013',
                'name' => 'Pak Cecep',
                'email' => 'cecep@gmail.com',
                'phone_country_code' => '+62',
                'phone_number' => '82255667793',
                'is_active' => true,
                'password' => bcrypt('password123'),
                'address' => 'Jl. Edelweis No. 13, RT 03 RW 07',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ============================================================
        // DATA DEMO: Paket Internet, Langganan, Tagihan, Pembayaran, Insentif
        // ============================================================
        $this->seedInternetPackages($company1Id);
        $this->seedInternetPackages($company2Id);
        $this->seedInternetPackages($company4Id);
        $this->seedInternetPackages($company5Id);

        $this->seedPaymentHistory($company1Id);
        $this->seedPaymentHistory($company2Id);
        $this->seedPaymentHistory($company4Id);
        $this->seedPaymentHistory($company5Id);

        $this->call(PermissionSeeder::class);
    }

    private function seedPaymentHistory(string $companyId): void
    {
        $adminUser = \App\Models\AdminCompany::where('company_id', $companyId)->first();
        $providers = ['internal', 'internal', 'internal', 'external'];
        $methods = ['tunai', 'transfer_manual'];
        $statuses = ['pending', 'paid', 'paid', 'paid', 'rejected'];
        $statusDescriptions = ['Menunggu konfirmasi', 'Pembayaran lunas', 'Lunas', 'Sudah dibayar', 'Ditolak'];

        $invoices = \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->where('payment_status', 'paid')
            ->inRandomOrder()
            ->take(rand(3, 6))
            ->get();

        foreach ($invoices as $invoice) {
            $provider = $providers[array_rand($providers)];
            $method = $methods[array_rand($methods)];
            $status = $statuses[array_rand($statuses)];
            $statusIdx = array_search($status, $statuses);
            $statusDesc = $statusDescriptions[$statusIdx];

            \App\Models\CustInternetPayment::create([
                'id' => Str::uuid(),
                'cust_internet_invc_id' => $invoice->id,
                'amount_paid' => $invoice->grand_total,
                'payment_date' => $invoice->paid_at ?? now()->subDays(rand(1, 30)),
                'provider' => $provider,
                'payment_method' => $method,
                'code' => 'BYR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'status' => $status,
                'status_description' => $statusDesc,
                'status_reason' => $status === 'paid' ? 'Disetujui oleh admin' : ($status === 'rejected' ? 'Bukti tidak jelas' : null),
                'proof_file' => null,
                'created_at' => $invoice->paid_at ?? now()->subDays(rand(1, 30)),
                'updated_at' => $invoice->paid_at ?? now()->subDays(rand(1, 30)),
            ]);
        }

        $unpaidInvoices = \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->where('payment_status', 'unpaid')
            ->inRandomOrder()
            ->take(rand(4, 8))
            ->get();

        // 30% unpaid invoice -> skenario "Sebagian" (cicilan/partial paid)
        // 70% -> skenario "Belum Bayar" (cuma payment pending)
        $partialThreshold = (int) ceil($unpaidInvoices->count() * 0.3);
        $idx = 0;
        foreach ($unpaidInvoices as $invoice) {
            $method = $methods[array_rand($methods)];
            $isPartial = $idx < $partialThreshold;
            $idx++;

            if ($isPartial) {
                // Partial: payment 40-80% dari grand_total, status=paid
                $partialRatio = rand(40, 80) / 100;
                $partialAmount = round((float) $invoice->grand_total * $partialRatio);
                $partialDate = now()->subDays(rand(1, 20));

                \App\Models\CustInternetPayment::create([
                    'id' => Str::uuid(),
                    'cust_internet_invc_id' => $invoice->id,
                    'amount_paid' => $partialAmount,
                    'payment_date' => $partialDate,
                    'provider' => 'internal',
                    'payment_method' => $method,
                    'code' => 'BYR-' . $partialDate->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'status' => 'paid',
                    'status_description' => 'Pembayaran cicilan diterima',
                    'status_reason' => 'Disetujui oleh admin',
                    'proof_file' => null,
                    'created_at' => $partialDate,
                    'updated_at' => $partialDate,
                ]);
            } else {
                // Belum bayar: payment pending (tidak dihitung di total_paid)
                \App\Models\CustInternetPayment::create([
                    'id' => Str::uuid(),
                    'cust_internet_invc_id' => $invoice->id,
                    'amount_paid' => $invoice->grand_total,
                    'payment_date' => now(),
                    'provider' => 'internal',
                    'payment_method' => $method,
                    'code' => 'BYR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                    'status' => 'pending',
                    'status_description' => 'Menunggu persetujuan',
                    'status_reason' => null,
                    'proof_file' => null,
                    'created_at' => now()->subDays(rand(1, 15)),
                    'updated_at' => now()->subDays(rand(1, 15)),
                ]);
            }
        }
    }

    private function seedInternetPackages(string $companyId): void
    {
        $packages = [
            ['code' => 'd05', 'name' => 'Daily 5Mbps', 'price' => 5000, 'speed_down_kbps' => 5120, 'speed_up_kbps' => 2560, 'quota_gb' => 10, 'billing_cycle' => 'daily', 'is_unlimited' => false],
            ['code' => 'w20', 'name' => 'Weekly 20Mbps', 'price' => 35000, 'speed_down_kbps' => 20480, 'speed_up_kbps' => 10240, 'quota_gb' => 50, 'billing_cycle' => 'weekly', 'is_unlimited' => false],
            ['code' => 'b10', 'name' => 'Basic 10Mbps', 'price' => 150000, 'speed_down_kbps' => 10240, 'speed_up_kbps' => 5120, 'quota_gb' => 100, 'billing_cycle' => 'monthly', 'is_unlimited' => false],
            ['code' => 'p25', 'name' => 'Pro 25Mbps', 'price' => 250000, 'speed_down_kbps' => 25600, 'speed_up_kbps' => 10240, 'quota_gb' => 300, 'billing_cycle' => 'monthly', 'is_unlimited' => false],
            ['code' => 'u50', 'name' => 'Ultimate 50Mbps', 'price' => 400000, 'speed_down_kbps' => 51200, 'speed_up_kbps' => 20480, 'quota_gb' => 500, 'billing_cycle' => 'monthly', 'is_unlimited' => true],
            ['code' => 'y30', 'name' => 'Yearly 30Mbps', 'price' => 3000000, 'speed_down_kbps' => 30720, 'speed_up_kbps' => 15360, 'quota_gb' => 1000, 'billing_cycle' => 'yearly', 'is_unlimited' => true],
        ];

        foreach ($packages as $pkg) {
            \App\Models\InternetPackage::create($pkg + [
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'is_active' => true,
            ]);

            // Buat langganan untuk customer acak
            $customers = Customer::where('company_id', $companyId)->inRandomOrder()->take(rand(2, 4))->get();
            foreach ($customers as $customer) {
                $langganan = \App\Models\CustInternet::create([
                    'id' => Str::uuid(),
                    'customer_id' => $customer->id,
                    'internet_package_id' => \App\Models\InternetPackage::where('company_id', $companyId)->where('name', $pkg['name'])->first()->id,
                    'account_number' => 'NET-' . strtoupper(Str::random(8)),
                    'router_sn' => 'rt-sn-' . Str::random(16),
                    'internet_status' => 'active'
                ]);

                // Generate 2-3 invoice untuk tiap langganan
                for ($i = 0; $i < rand(2, 3); $i++) {
                    $usageStart = now()->subMonths($i)->startOfMonth();
                    $usageEnd = now()->subMonths($i)->endOfMonth();
                    $dueDate = now()->subMonths($i)->addDays(15);
                    $status = $i === 0 ? 'unpaid' : 'paid';
                    \App\Models\CustInternetInvc::create([
                        'id' => Str::uuid(),
                        'cust_internet_id' => $langganan->id,
                        'invoice_number' => 'INV-' . now()->subMonths($i)->format('Ymd') . '-' . strtoupper(Str::random(6)),
                        'usage_start_date' => $usageStart,
                        'usage_end_date' => $usageEnd,
                        'amount' => $pkg['price'],
                        'total_amount' => $pkg['price'],
                        'grand_total' => $pkg['price'],
                        'cycle' => $pkg['billing_cycle'],
                        'due_date' => $dueDate,
                        'payment_status' => $status,
                        'status' => $status,
                        'status_description' => $status === 'paid' ? 'Lunas' : 'Menunggu pembayaran',
                        'paid_at' => $status === 'paid' ? now()->subMonths($i)->addDays(rand(1, 10)) : null,
                    ]);
                }
            }
        }

        // Buat data insentif utk perusahaan
        $incentiveNames = ['Komisi Tagih', 'Bonus Pelanggan Baru', 'Insentif Bulanan'];
        $incentiveTypes = ['fixed', 'percentage', 'fixed'];
        $incentiveValues = [10000, 5, 50000];
        foreach ($incentiveNames as $idx => $name) {
            $insentif = \App\Models\EmpIncentive::create([
                'id' => Str::uuid(),
                'company_id' => $companyId,
                'code' => 'INS' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
                'name' => $incentiveNames[$idx],
                'type' => $incentiveTypes[$idx],
                'value' => $incentiveValues[$idx],
                'is_active' => true,
            ]);

            // Buat 2-3 riwayat insentif per insentif
            $invoices = \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->inRandomOrder()->take(rand(2, 3))->get();
            $adminUser = \App\Models\AdminCompany::where('company_id', $companyId)->first();
            $statuses = ['pending', 'approved', 'approved', 'rejected', 'pending'];
            $reasons = ['Insentif bulanan', 'Insentif performa', 'Insentif kehadiran', 'Bonus project', 'Insentif overtime'];
            $reviewReasons = ['Sesuai ketentuan', 'Approved', 'Luar biasa', null, 'Kurang memenuhi target'];

            foreach ($invoices as $invoice) {
                $status = $statuses[array_rand($statuses)];
                $amount = $insentif->type === 'percentage' ? ($invoice->grand_total * $insentif->value / 100) : $insentif->value;

                $log = \App\Models\EmpIncentiveLog::create([
                    'emp_incentive_id' => $insentif->id,
                    'cust_internet_invcs_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'submitted_by_type' => \App\Models\AdminCompany::class,
                    'submitted_by_id' => $adminUser?->id,
                    'submitted_by_name' => $adminUser?->name,
                    'amount' => $amount,
                    'date' => now()->subDays(rand(1, 60)),
                    'reason' => $reasons[array_rand($reasons)],
                    'review_status' => $status,
                    'reviewed_at' => $status !== 'pending' ? now()->subDays(rand(0, 5)) : null,
                    'reviewed_by_type' => $status !== 'pending' ? \App\Models\AdminCompany::class : null,
                    'reviewed_by_id' => $status !== 'pending' ? $adminUser?->id : null,
                    'review_reason' => $status === 'rejected' ? 'Kurang memenuhi target quarterly' : ($reviewReasons[array_rand($reviewReasons)]),
                ]);
            }
        }
    }
}

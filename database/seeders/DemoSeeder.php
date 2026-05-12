<?php

namespace Database\Seeders;

use App\Models\AdminSaas;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Operator SaaS accounts
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

        // Dummy companies
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
    }
}

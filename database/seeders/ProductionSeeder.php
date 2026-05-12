<?php

namespace Database\Seeders;

use App\Models\AdminSaas;
use App\Models\Company;
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

        Company::query()->insert([
            'id' => Str::uuid(),
            'name' => 'PT Net Sejahtera',
            'email' => 'info@netsejahtera.id',
            'phone_country_code' => '+62',
            'phone_number' => '82112345678',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

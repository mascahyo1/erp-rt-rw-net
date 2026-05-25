<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdminCompany;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$companyId = App\Models\Company::first()->id ?? null;
if (!$companyId) {
    echo "ERROR: No company found. Run DemoSeeder first.\n";
    exit(1);
}

// Get role IDs to delete role_permissions first
$rolesToDelete = Role::whereIn('name', ['RBAC Full Access', 'RBAC List Only', 'RBAC No Paket', 'RBAC Customer Full', 'RBAC Customer List'])->pluck('id');

if ($rolesToDelete->count() > 0) {
    DB::table('model_has_roles')->whereIn('role_id', $rolesToDelete)->delete();
    DB::table('role_permissions')->whereIn('role_id', $rolesToDelete)->delete();
    Role::whereIn('id', $rolesToDelete)->forceDelete();
}

DB::table('model_has_roles')->whereIn('model_type', [AdminCompany::class])
    ->whereIn('model_id', function($q) {
        $q->select('id')->from('admin_companies')
          ->whereIn('email', ['rbac.full@rtrwnet.id','rbac.list@rtrwnet.id','rbac.no@rtrwnet.id']);
    })->delete();

AdminCompany::whereIn('email', ['rbac.full@rtrwnet.id','rbac.list@rtrwnet.id','rbac.no@rtrwnet.id'])->forceDelete();

echo "Deleted existing RBAC users/roles\n";

// ========== RBAC FULL ACCESS (ALL customer + paket permissions) ==========
$fullRole = Role::create([
    'id' => Str::uuid()->toString(),
    'scope' => 'admin_perusahaan',
    'name' => 'RBAC Full Access',
    'is_active' => 1,
    'display_order' => 1
]);

$fullPerms = Permission::whereIn('name', [
    'perusahaan-saya.detail','perusahaan-saya.edit',
    'paket.list','paket.create','paket.edit','paket.delete','paket.restore','paket.export','paket.import','paket.detail',
    'customer.list','customer.create','customer.edit','customer.delete','customer.restore','customer.export','customer.import','customer.detail',
    'langganan.list','langganan.create','langganan.edit','langganan.delete','langganan.restore','langganan.export','langganan.import'
])->get();
foreach ($fullPerms as $p) {
    DB::table('role_permissions')->insert([
        'id' => Str::uuid()->toString(),
        'role_id' => $fullRole->id,
        'permission_id' => $p->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$fullUser = AdminCompany::create([
    'name' => 'RBAC Full Access',
    'email' => 'rbac.full@rtrwnet.id',
    'password' => bcrypt('password'),
    'company_id' => $companyId,
    'is_active' => 1,
    'phone_country_code' => '+62',
    'phone_number' => '811' . rand(10000000, 99999999)
]);

DB::table('model_has_roles')->insert([
    'id' => Str::uuid()->toString(),
    'role_id' => $fullRole->id,
    'model_id' => $fullUser->id,
    'model_type' => AdminCompany::class
]);

echo "Created: rbac.full@rtrwnet.id / password (FULL - all customer + paket permissions)\n";

// ========== RBAC LIST ONLY (customer.list only) ==========
$listRole = Role::create([
    'id' => Str::uuid()->toString(),
    'scope' => 'admin_perusahaan',
    'name' => 'RBAC List Only',
    'is_active' => 1,
    'display_order' => 2
]);

$listPerm = Permission::where('name', 'customer.list')->first();
if ($listPerm) {
    DB::table('role_permissions')->insert([
        'id' => Str::uuid()->toString(),
        'role_id' => $listRole->id,
        'permission_id' => $listPerm->id,
        'created_at' => now(),
        'updated_at' => now()
    ]);
}

$listUser = AdminCompany::create([
    'name' => 'RBAC List Only',
    'email' => 'rbac.list@rtrwnet.id',
    'password' => bcrypt('password'),
    'company_id' => $companyId,
    'is_active' => 1,
    'phone_country_code' => '+62',
    'phone_number' => '811' . rand(10000000, 99999999)
]);

DB::table('model_has_roles')->insert([
    'id' => Str::uuid()->toString(),
    'role_id' => $listRole->id,
    'model_id' => $listUser->id,
    'model_type' => AdminCompany::class
]);

echo "Created: rbac.list@rtrwnet.id / password (LIST only - customer.list)\n";

// ========== RBAC NO PERMISSION ==========
$noRole = Role::create([
    'id' => Str::uuid()->toString(),
    'scope' => 'admin_perusahaan',
    'name' => 'RBAC No Paket',
    'is_active' => 1,
    'display_order' => 3
]);

$noUser = AdminCompany::create([
    'name' => 'RBAC No Permission',
    'email' => 'rbac.no@rtrwnet.id',
    'password' => bcrypt('password'),
    'company_id' => $companyId,
    'is_active' => 1,
    'phone_country_code' => '+62',
    'phone_number' => '811' . rand(10000000, 99999999)
]);

DB::table('model_has_roles')->insert([
    'id' => Str::uuid()->toString(),
    'role_id' => $noRole->id,
    'model_id' => $noUser->id,
    'model_type' => AdminCompany::class
]);

echo "Created: rbac.no@rtrwnet.id / password (NO permission)\n";
echo "\n=== RBAC Users Ready ===\n";
echo "rbac.full@rtrwnet.id / password (ALL customer + paket permissions)\n";
echo "rbac.list@rtrwnet.id / password (customer.list only)\n";
echo "rbac.no@rtrwnet.id / password (NO permission)\n";
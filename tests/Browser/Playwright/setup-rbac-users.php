<?php
require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdminCompany;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$companyId = App\Models\Company::first()->id ?? 1;

// Get role IDs to delete role_permissions first
$rolesToDelete = Role::whereIn('name', ['RBAC Full Access', 'RBAC List Only', 'RBAC No Paket'])->pluck('id');

// Delete in correct order: model_has_roles -> role_permissions -> roles
if ($rolesToDelete->count() > 0) {
    // First delete model_has_roles entries
    DB::table('model_has_roles')->whereIn('role_id', $rolesToDelete)->delete();
    // Then delete role_permissions
    DB::table('role_permissions')->whereIn('role_id', $rolesToDelete)->delete();
    // Finally delete roles
    Role::whereIn('id', $rolesToDelete)->forceDelete();
}

// Delete existing RBAC users
AdminCompany::whereIn('email', ['rbac.full@rtrwnet.id','rbac.list@rtrwnet.id','rbac.no@rtrwnet.id'])->forceDelete();

echo "Deleted existing RBAC users/roles\n";

// Full Access Role
$fullRole = Role::create([
    'id' => Str::uuid()->toString(),
    'scope' => 'admin_perusahaan',
    'name' => 'RBAC Full Access',
    'is_active' => 1,
    'display_order' => 1
]);

$fullPerms = Permission::whereIn('name', ['paket.list','paket.create','paket.edit','paket.delete','paket.export','paket.import'])->get();
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

echo "Created: rbac.full@rtrwnet.id / password (FULL)\n";

// List Only Role
$listRole = Role::create([
    'id' => Str::uuid()->toString(),
    'scope' => 'admin_perusahaan',
    'name' => 'RBAC List Only',
    'is_active' => 1,
    'display_order' => 2
]);

$listPerm = Permission::where('name','paket.list')->first();
DB::table('role_permissions')->insert([
    'id' => Str::uuid()->toString(),
    'role_id' => $listRole->id,
    'permission_id' => $listPerm->id,
    'created_at' => now(),
    'updated_at' => now()
]);

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

echo "Created: rbac.list@rtrwnet.id / password (LIST ONLY)\n";

// No Permission Role
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

echo "Created: rbac.no@rtrwnet.id / password (NO PERMISSION)\n";
echo "\n=== RBAC Users Ready ===\n";
echo "rbac.full@rtrwnet.id / password (ALL permissions)\n";
echo "rbac.list@rtrwnet.id / password (LIST only)\n";
echo "rbac.no@rtrwnet.id / password (NO permission)\n";
<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Roles ===\n";
$roles = App\Models\Role::all();
foreach ($roles as $role) {
    echo "Role: {$role->name} (scope: {$role->scope}, company: {$role->company_id})\n";
}

echo "\n=== Permissions ===\n";
$perms = App\Models\Permission::all()->take(20);
foreach ($perms as $perm) {
    echo "- {$perm->name}\n";
}

echo "\n=== AdminCompanies with roles ===\n";
$admins = App\Models\AdminCompany::with('roles')->take(5)->get();
foreach ($admins as $admin) {
    echo "Admin: {$admin->email} - Roles: ";
    foreach ($admin->roles as $role) {
        echo $role->name . " ";
    }
    echo "\n";
}
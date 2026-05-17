<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = \App\Models\AdminCompany::where('email', 'admin@netsejahtera.com')->first();
echo 'User: ' . ($u ? $u->name : 'NOT FOUND') . PHP_EOL;

if ($u) {
    $roles = $u->roles()->pluck('name')->toArray();
    echo 'Roles: ' . implode(', ', $roles) . PHP_EOL;
    echo 'canPermission paket.list: ' . ($u->canPermission('paket.list') ? 'YES' : 'NO') . PHP_EOL;
    $names = $u->getAllPermissionNames();
    echo 'Total perms: ' . count($names) . PHP_EOL;
    echo 'First 10: ' . implode(', ', array_slice($names, 0, 10)) . PHP_EOL;
}

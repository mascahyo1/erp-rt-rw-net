<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo "Roles admin_perusahaan:\n";
$r = \App\Models\Role::where('scope','admin_perusahaan')->get();
foreach($r as $x) echo '  ' . $x->name . ' (company_id=' . ($x->company_id ?? 'NULL') . ')' . "\n";
echo "Total: " . $r->count() . "\n";
echo "\nAdminCompany perms:\n";
$a = \App\Models\AdminCompany::where('email','admin@netsejahtera.com')->first();
if($a){ $p = $a->getAllPermissionNames(); echo 'Total perms: '.count($p)."\n"; echo 'role-perusahaan-op.list: '.(in_array('role-perusahaan-op.list',$p)?'YES':'NO')."\n"; echo 'role-web-karyawan.list: '.(in_array('role-web-karyawan.list',$p)?'YES':'NO')."\n"; }

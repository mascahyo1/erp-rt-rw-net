<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Simulate request to role-web-karyawan
$req = \Illuminate\Http\Request::create('/operator-perusahaan/role-web-karyawan', 'GET');

// Boot authenticated session manually
// Just check what HandleInertiaRequests would return
$middleware = new \App\Http\Middleware\HandleInertiaRequests;
$ref = new ReflectionMethod($middleware, 'share');
$ref->setAccessible(true);

// We need the request to have session + auth
// Let's just test the guard detection logic
echo 'Path: /operator-perusahaan/role-web-karyawan/' . "\n";
$path = '/operator-perusahaan/role-web-karyawan/';

$guardMap = [
    '/operator-saas/' => 'admin-saas',
    '/operator-perusahaan/' => 'admin-company',
    '/karyawan/' => 'employee',
    '/customer/' => 'customer',
];

$matchedGuard = null;
foreach ($guardMap as $prefix => $guard) {
    echo '  Testing: ' . $path . ' vs ' . $prefix . ' -> ' . (str_starts_with($path, $prefix) ? 'MATCH' : 'no') . "\n";
    if (str_starts_with($path, $prefix)) {
        $matchedGuard = $guard;
        break;
    }
}
echo 'Matched guard: ' . ($matchedGuard ?? 'NONE') . "\n";
echo 'str_starts_with: ' . (str_starts_with('/operator-perusahaan/role-web-karyawan/', '/operator-perusahaan/') ? 'TRUE' : 'FALSE') . "\n";

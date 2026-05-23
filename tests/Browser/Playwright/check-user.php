<?php
require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\AdminCompany::where('email', 'test@playwright.dev')->first();
echo $user ? "User EXISTS: " . $user->email . PHP_EOL : "User NOT FOUND" . PHP_EOL;
if ($user) {
    echo "Password hash exists: " . substr($user->password, 0, 10) . "..." . PHP_EOL;
    echo "Company ID: " . $user->company_id . PHP_EOL;
    echo "Is Active: " . ($user->is_active ? 'true' : 'false') . PHP_EOL;
}
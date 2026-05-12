<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AdminSaas;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run migrations
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate', ['--force' => true]);

// Create user
$user = new AdminSaas();
$user->name = 'Test';
$user->email = 'test@example.com';
$user->phone_country_code = '+62';
$user->phone_number = '81234567890';
$user->password = Hash::make('password');
$user->save();

// Simulate the login controller logic
echo 'User created: ' . $user->id . PHP_EOL;
echo 'User email: ' . $user->email . PHP_EOL;
echo 'User is_active: ' . ($user->is_active ? 'true' : 'false') . PHP_EOL;

$credentials = ['email' => 'test@example.com', 'password' => 'password'];
$found = AdminSaas::where('email', $credentials['email'])->first();
echo 'Found user: ' . ($found ? 'yes' : 'no') . PHP_EOL;
echo 'Hash check: ' . (Hash::check($credentials['password'], $found->password) ? 'pass' : 'fail') . PHP_EOL;

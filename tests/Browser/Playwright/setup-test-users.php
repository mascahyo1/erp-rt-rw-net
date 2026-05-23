<?php
// Setup test users for Playwright

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Company;

echo "=== Membuat User Test ===\n\n";

// Create Company if not exists
$company = Company::first();
if (!$company) {
    $company = Company::create([
        'name' => 'Test Company',
        'code' => 'TEST01',
        'is_active' => true,
    ]);
    echo "Created Company: {$company->name}\n";
}

// AdminCompany
$adminCompany = AdminCompany::first();
if (!$adminCompany) {
    $adminCompany = AdminCompany::create([
        'name' => 'Test Admin Perusahaan',
        'email' => 'admin@perusahaan.rtrwnet.id',
        'password' => bcrypt('password'),
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    echo "Created AdminCompany: {$adminCompany->email}\n";
} else {
    echo "AdminCompany exists: {$adminCompany->email}\n";
}

// AdminSaas
$adminSaas = AdminSaas::first();
if (!$adminSaas) {
    $adminSaas = AdminSaas::create([
        'name' => 'Test Admin SaaS',
        'email' => 'admin@saas.rtrwnet.id',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    echo "Created AdminSaas: {$adminSaas->email}\n";
} else {
    echo "AdminSaas exists: {$adminSaas->email}\n";
}

// Employee
$employee = Employee::first();
if (!$employee) {
    $employee = Employee::create([
        'name' => 'Test Karyawan',
        'email' => 'karyawan@rtrwnet.id',
        'password' => bcrypt('password'),
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    echo "Created Employee: {$employee->email}\n";
} else {
    echo "Employee exists: {$employee->email}\n";
}

// Customer
$customer = Customer::first();
if (!$customer) {
    $customer = Customer::create([
        'name' => 'Test Pelanggan',
        'email' => 'pelanggan@rtrwnet.id',
        'password' => bcrypt('password'),
        'company_id' => $company->id,
        'is_active' => true,
    ]);
    echo "Created Customer: {$customer->email}\n";
} else {
    echo "Customer exists: {$customer->email}\n";
}

echo "\n=== Credentials ===\n";
echo "Admin Perusahaan: admin@perusahaan.rtrwnet.id / password\n";
echo "Admin SaaS: admin@saas.rtrwnet.id / password\n";
echo "Karyawan: karyawan@rtrwnet.id / password\n";
echo "Pelanggan: pelanggan@rtrwnet.id / password\n";
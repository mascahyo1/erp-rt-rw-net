<?php

namespace Tests\Browser;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_operator_saas_can_login_and_see_dashboard(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true, 'name' => 'Super Admin Demo']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitForText('Operator SaaS')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Masuk')
                ->waitForText('Super Admin Demo', 10)
                ->assertPathIs('/operator-saas/dashboard');
        });
    }

    public function test_operator_saas_login_fails_with_wrong_password(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitForText('Operator SaaS')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->press('Masuk')
                ->pause(1000)
                ->assertPathIs('/login-operator-saas')
                ->assertSee('Email atau password salah');
        });
    }

    public function test_operator_perusahaan_can_login_and_see_dashboard(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true, 'name' => 'Admin Perusahaan']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-perusahaan')
                ->waitForText('Masuk Perusahaan')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Masuk')
                ->waitForText('Admin Perusahaan', 10)
                ->assertPathIs('/operator-perusahaan/dashboard');
        });
    }

    public function test_operator_perusahaan_login_fails_with_inactive_account(): void
    {
        $user = AdminCompany::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-perusahaan')
                ->waitForText('Masuk Perusahaan')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Masuk')
                ->pause(1000)
                ->assertPathIs('/login-perusahaan')
                ->assertSee('Akun anda dinonaktifkan');
        });
    }

    public function test_karyawan_can_login_and_see_dashboard(): void
    {
        $user = Employee::factory()->create(['is_active' => true, 'name' => 'Ahmad Karyawan']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-karyawan')
                ->waitForText('Login Karyawan')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Masuk')
                ->waitForText('Ahmad Karyawan', 10)
                ->assertPathIs('/karyawan/dashboard');
        });
    }

    public function test_pelanggan_can_login_and_see_dashboard(): void
    {
        $user = Customer::factory()->create(['is_active' => true, 'name' => 'Pak Sugeng']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-pelanggan')
                ->waitForText('Masuk')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->press('Masuk')
                ->waitForText('Pak Sugeng', 10)
                ->assertPathIs('/customer/dashboard');
        });
    }

    public function test_guest_cannot_access_protected_pages(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator-saas/dashboard')
                ->waitForLocation('/login-operator-saas')
                ->assertPathIs('/login-operator-saas');

            $browser->visit('/operator-perusahaan/dashboard')
                ->waitForLocation('/login-perusahaan')
                ->assertPathIs('/login-perusahaan');

            $browser->visit('/karyawan/dashboard')
                ->waitForLocation('/login-karyawan')
                ->assertPathIs('/login-karyawan');

            $browser->visit('/customer/dashboard')
                ->waitForLocation('/login-pelanggan')
                ->assertPathIs('/login-pelanggan');
        });
    }
}

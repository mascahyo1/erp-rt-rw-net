<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_case_berhasil(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]')
                ->assertPresent('button[type="submit"]')
                ->screenshot('operator-saas/login/test_case_berhasil/01-page');

            $browser->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/test_case_berhasil/02-form-filled')
                ->click('button[type="submit"]')
                ->pause(3000)
                ->screenshot('operator-saas/login/test_case_berhasil/03-after-login')
                ->assertPathIs('/operator-saas/dashboard');
        });
    }

    public function test_case_password_salah(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('operator-saas/login/test_case_password_salah/01-form-filled')
                ->click('button[type="submit"]')
                ->waitFor('p.text-red-500', 10)
                ->screenshot('operator-saas/login/test_case_password_salah/02-error-shown')
                ->assertPathIs('/login-operator-saas')
                ->assertSeeIn('p.text-red-500', 'credentials');
        });
    }

    public function test_case_akun_tidak_aktif(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/test_case_akun_tidak_aktif/01-form-filled')
                ->click('button[type="submit"]')
                ->waitFor('p.text-red-500', 10)
                ->screenshot('operator-saas/login/test_case_akun_tidak_aktif/02-error-shown')
                ->assertPathIs('/login-operator-saas')
                ->assertSeeIn('p.text-red-500', 'dinonaktifkan');
        });
    }

    public function test_case_sudah_login_ga_perlu_login_lagi(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            // Login first
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/test_case_sudah_login_ga_perlu_login_lagi/01-login-first')
                ->click('button[type="submit"]')
                ->pause(3000)
                ->assertPathIs('/operator-saas/dashboard');

            // Visit login page again - should redirect to dashboard
            $browser->visit('/login-operator-saas')
                ->pause(2000)
                ->screenshot('operator-saas/login/test_case_sudah_login_ga_perlu_login_lagi/02-redirected-to-dashboard')
                ->assertPathIs('/operator-saas/dashboard');
        });
    }

    public function test_case_throttled(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->screenshot('operator-saas/login/test_case_throttled/01-page');

            // Submit wrong password 6 times to trigger throttle (limit: 5/min)
            for ($i = 0; $i < 6; $i++) {
                $browser->type('input[type="email"]', $user->email)
                    ->type('input[type="password"]', 'wrong')
                    ->click('button[type="submit"]')
                    ->pause(500);
            }

            $browser->pause(2000)
                ->screenshot('operator-saas/login/test_case_throttled/02-throttled')
                ->assertPathIs('/login-operator-saas');
        });
    }
}

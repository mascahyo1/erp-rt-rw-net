<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    public function test_case_berhasil(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
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
            $browser->driver->manage()->deleteAllCookies();
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
            $browser->driver->manage()->deleteAllCookies();
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
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/test_case_sudah_login_ga_perlu_login_lagi/01-login-first')
                ->click('button[type="submit"]')
                ->pause(3000)
                ->assertPathIs('/operator-saas/dashboard');

            $browser->visit('/login-operator-saas')
                ->pause(2000)
                ->screenshot('operator-saas/login/test_case_sudah_login_ga_perlu_login_lagi/02-redirected-to-dashboard')
                ->assertPathIs('/operator-saas/dashboard');
        });
    }

    public function test_case_throttled(): void
    {
        Cache::flush();

        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/login-operator-saas')
                ->pause(3000)
                ->screenshot('operator-saas/login/test_case_throttled/01-page');

            $screenshotIdx = 2;
            for ($i = 1; $i <= 6; $i++) {
                $browser->type('input[type="email"]', $user->email)
                    ->type('input[type="password"]', 'wrong')
                    ->click('button[type="submit"]')
                    ->pause(800)
                    ->screenshot('operator-saas/login/test_case_throttled/' . str_pad($screenshotIdx, 2, '0', STR_PAD_LEFT) . '-attempt-' . ($i + 1));
                $screenshotIdx++;
            }

            $browser->pause(2000)
                ->screenshot('operator-saas/login/test_case_throttled/' . str_pad($screenshotIdx, 2, '0', STR_PAD_LEFT) . '-throttled-final')
                ->assertSeeIn('span', '429')
                ->assertPathIs('/login-operator-saas');
        });
    }

    public function test_case_sudah_login_lalu_dinonaktifkan(): void
    {
        Cache::flush();
        $password = 'Password123';
        $user = AdminSaas::factory()->create(['is_active' => true, 'password' => $password]);

        $this->browse(function (Browser $browser) use ($user, $password) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/login-operator-saas')
                ->pause(3000)
                ->screenshot('operator-saas/login/test_case_sudah_login_lalu_dinonaktifkan/01-page');
            $browser
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', $password)
                ->click('button[type="submit"]')
                ->screenshot('operator-saas/login/test_case_sudah_login_lalu_dinonaktifkan/02-input-cred')
                ->pause(2000)
                ->screenshot('operator-saas/login/test_case_sudah_login_lalu_dinonaktifkan/03-login-berhasil')
                ->assertPathIs('/operator-saas/dashboard');
            $user->update([
                'is_active' => false
            ]);
            $browser->refresh()
            ->pause(2000)
            ->screenshot('operator-saas/login/test_case_sudah_login_lalu_dinonaktifkan/04-akun-dinonaktifkan')
            ->assertPathIs('/login-operator-saas');

                
        });

    }
}

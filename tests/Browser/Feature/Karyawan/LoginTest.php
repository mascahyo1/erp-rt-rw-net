<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    public function test_page_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login-karyawan')
                ->waitForText('Login Karyawan', 10)
                ->screenshot('karyawan/login/01-page')
                ->assertSee('Login Karyawan')
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]');
        });
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-karyawan')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('karyawan/login/02-before-submit')
                ->click('button[type="submit"]')
                ->pause(2000)
                ->screenshot('karyawan/login/03-error-shown')
                ->assertPathIs('/login-karyawan');
        });
    }

    public function test_inactive_user_rejected(): void
    {
        $user = Employee::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-karyawan')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('karyawan/login/04-inactive-before')
                ->click('button[type="submit"]')
                ->pause(2000)
                ->screenshot('karyawan/login/05-inactive-after')
                ->assertPathIs('/login-karyawan');
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/login/06-guest-redirect')
                ->assertPathIs('/login-karyawan');
        });
    }
}

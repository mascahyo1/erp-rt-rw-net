<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    public function test_page_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login-perusahaan')
                ->waitForText('Masuk Perusahaan', 10)
                ->screenshot('operator-perusahaan/login/01-page')
                ->assertSee('Masuk Perusahaan')
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]');
        });
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-perusahaan')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('operator-perusahaan/login/02-before-submit')
                ->click('button[type="submit"]')
                ->pause(2000)
                ->screenshot('operator-perusahaan/login/03-error-shown')
                ->assertPathIs('/login-perusahaan');
        });
    }

    public function test_inactive_user_rejected(): void
    {
        $user = AdminCompany::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-perusahaan')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-perusahaan/login/04-inactive-before')
                ->click('button[type="submit"]')
                ->pause(2000)
                ->screenshot('operator-perusahaan/login/05-inactive-after')
                ->assertPathIs('/login-perusahaan');
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator-perusahaan/dashboard')
                ->pause(800)
                ->screenshot('operator-perusahaan/login/06-guest-redirect')
                ->assertPathIs('/login-perusahaan');
        });
    }
}

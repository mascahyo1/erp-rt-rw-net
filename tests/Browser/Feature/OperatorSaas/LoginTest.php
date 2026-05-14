<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_page_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]')
                ->assertPresent('button[type="submit"]')
                ->screenshot('operator-saas/login/01-page');
        });
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('operator-saas/login/02-before-submit')
                ->click('button[type="submit"]')
                ->waitFor('p.text-red-500', 10)
                ->screenshot('operator-saas/login/03-after-submit')
                ->assertPathIs('/login-operator-saas')
                ->assertSeeIn('p.text-red-500', 'credentials');

            dump('[error-text] ' . $browser->driver->executeScript(
                "const el = document.querySelector('p.text-red-500'); return el ? el.textContent.trim() : 'NOT_FOUND';"
            ));
        });
    }

    public function test_inactive_user_shows_error(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitFor('button[type="submit"]', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/04-inactive-before')
                ->click('button[type="submit"]')
                ->waitFor('p.text-red-500', 10)
                ->screenshot('operator-saas/login/05-inactive-after')
                ->assertPathIs('/login-operator-saas')
                ->assertSeeIn('p.text-red-500', 'dinonaktifkan');
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator-saas/dashboard')
                ->waitFor('button[type="submit"]', 10)
                ->screenshot('operator-saas/login/06-guest-redirect')
                ->assertPathIs('/login-operator-saas');
        });
    }
}

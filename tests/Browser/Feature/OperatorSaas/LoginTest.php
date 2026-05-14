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
                ->screenshot('operator-saas/login/01-page')
                ->assertSee('Operator SaaS')
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]');
        });
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('operator-saas/login/02-before-submit')
                ->press('Masuk')
                ->pause(2000)
                ->screenshot('operator-saas/login/03-error-shown')
                ->assertPathIs('/login-operator-saas');
        });
    }

    public function test_inactive_user_rejected(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/04-inactive-before')
                ->press('Masuk')
                ->pause(2000)
                ->screenshot('operator-saas/login/05-inactive-after')
                ->assertPathIs('/login-operator-saas');
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator-saas/dashboard')
                ->pause(800)
                ->screenshot('operator-saas/login/06-guest-redirect')
                ->assertPathIs('/login-operator-saas');
        });
    }
}

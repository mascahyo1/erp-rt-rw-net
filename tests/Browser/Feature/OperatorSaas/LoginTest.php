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
                ->waitForText('Operator SaaS', 10)
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
                ->waitForText('Operator SaaS', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('operator-saas/login/02-before-submit')
                ->press('Masuk')
                ->waitForText('Masuk', 10)
                ->screenshot('operator-saas/login/03-after-submit')
                ->assertPathIs('/login-operator-saas');

            // Check error element exists in DOM
            $hasError = $browser->driver->executeScript(
                "return document.querySelector('p.text-red-500') !== null"
            );
            dump('[03-error-visible] ' . ($hasError ? 'YES' : 'NO'));

            $hasBorderError = $browser->driver->executeScript(
                "return document.querySelector('input.border-red-400') !== null"
            );
            dump('[03-input-red-border] ' . ($hasBorderError ? 'YES' : 'NO'));

            $errorText = $browser->driver->executeScript(
                "const el = document.querySelector('p.text-red-500'); return el ? el.textContent : 'NOT_FOUND';"
            );
            dump('[03-error-text] ' . $errorText);

            // Save source for manual check
            $source = $browser->driver->getPageSource();
            file_put_contents('tests/Browser/source/03-after-submit.html', $source);
        });
    }

    public function test_inactive_user_shows_error(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-operator-saas')
                ->waitForText('Operator SaaS', 10)
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('operator-saas/login/04-inactive-before')
                ->press('Masuk')
                ->waitForText('Masuk', 10)
                ->screenshot('operator-saas/login/05-inactive-after')
                ->assertPathIs('/login-operator-saas');

            $hasError = $browser->driver->executeScript(
                "return document.querySelector('p.text-red-500') !== null"
            );
            dump('[05-error-visible] ' . ($hasError ? 'YES' : 'NO'));

            $errorText = $browser->driver->executeScript(
                "const el = document.querySelector('p.text-red-500'); return el ? el.textContent : 'NOT_FOUND';"
            );
            dump('[05-error-text] ' . $errorText);

            $source = $browser->driver->getPageSource();
            file_put_contents('tests/Browser/source/05-inactive-after.html', $source);
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/operator-saas/dashboard')
                ->waitForText('Operator SaaS', 10)
                ->screenshot('operator-saas/login/06-guest-redirect')
                ->assertPathIs('/login-operator-saas');
        });
    }
}

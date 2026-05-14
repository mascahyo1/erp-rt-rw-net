<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_page_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login-pelanggan')
                ->screenshot('pelanggan/login/01-page')
                ->assertSee('Masuk')
                ->assertPresent('input[type="email"]')
                ->assertPresent('input[type="password"]');
        });
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-pelanggan')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'wrong-password')
                ->screenshot('pelanggan/login/02-before-submit')
                ->press('Masuk')
                ->pause(2000)
                ->screenshot('pelanggan/login/03-error-shown')
                ->assertPathIs('/login-pelanggan');
        });
    }

    public function test_inactive_user_rejected(): void
    {
        $user = Customer::factory()->inactive()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login-pelanggan')
                ->type('input[type="email"]', $user->email)
                ->type('input[type="password"]', 'password')
                ->screenshot('pelanggan/login/04-inactive-before')
                ->press('Masuk')
                ->pause(2000)
                ->screenshot('pelanggan/login/05-inactive-after')
                ->assertPathIs('/login-pelanggan');
        });
    }

    public function test_guest_redirect_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/customer/dashboard')
                ->pause(800)
                ->screenshot('pelanggan/login/06-guest-redirect')
                ->assertPathIs('/login-pelanggan');
        });
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('customer.login'));

        $response->assertOk();
    }

    public function test_login_screen_redirects_when_already_authenticated(): void
    {
        $user = Customer::factory()->create();

        $response = $this->actingAs($user, 'customer')
            ->get(route('customer.login'));

        $response->assertRedirect(route('customer.dashboard'));
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = Customer::factory()->create();

        $response = $this->post(route('customer.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('customer');
        $response->assertRedirect(route('customer.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = Customer::factory()->create();

        $response = $this->post(route('customer.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('customer');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_users_can_logout(): void
    {
        $user = Customer::factory()->create();

        $response = $this->actingAs($user, 'customer')
            ->post('/logout-pelanggan');

        $this->assertGuest('customer');
        $response->assertRedirect(route('landing.home'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('customer.dashboard'));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_users_cannot_authenticate_when_inactive(): void
    {
        $user = Customer::factory()->inactive()->create();

        $response = $this->post(route('customer.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('customer');
        $response->assertSessionHasErrors(['email' => 'Akun anda dinonaktifkan. Hubungi admin.']);
    }
}

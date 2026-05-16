<?php

namespace Tests\Feature\Auth;

use App\Models\AdminSaas;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;


{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('operator-saas.login'));

        $response->assertOk();
    }

    public function test_login_screen_redirects_when_already_authenticated(): void
    {
        $user = AdminSaas::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->get(route('operator-saas.login'));

        $response->assertRedirect(route('operator-saas.dashboard'));
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = AdminSaas::factory()->create();

        $response = $this->post(route('operator-saas.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $response->assertRedirect(route('operator-saas.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = AdminSaas::factory()->create();

        $response = $this->post(route('operator-saas.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('web');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_users_cannot_authenticate_when_inactive(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $response = $this->post(route('operator-saas.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('web');
        $response->assertSessionHasErrors(['email' => 'Akun anda dinonaktifkan. Hubungi admin.']);
    }

    public function test_inactive_user_is_logged_out_and_gets_error_when_accessing_protected_page(): void
    {
        $user = AdminSaas::factory()->inactive()->create();

        $response = $this->actingAs($user, 'web')
            ->get(route('operator-saas.dashboard'));

        $response->assertRedirect(route('operator-saas.login'));
        $this->assertGuest('web');
        $response->assertSessionHas('error', 'Akun anda dinonaktifkan. Hubungi admin.');
    }

    public function test_active_user_can_access_protected_page(): void
    {
        $user = AdminSaas::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->get(route('operator-saas.dashboard'));

        $response->assertOk();
    }

    public function test_users_can_logout(): void
    {
        $user = AdminSaas::factory()->create();

        $response = $this->actingAs($user, 'web')
            ->post('/logout-operator-saas');

        $this->assertGuest('web');
        $response->assertRedirect(route('landing.home'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('operator-saas.dashboard'));

        $response->assertRedirect(route('operator-saas.login'));
    }
}

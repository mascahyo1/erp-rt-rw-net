<?php

namespace Tests\Feature\Auth;

use App\Models\AdminCompany;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('operator-perusahaan.login'));

        $response->assertOk();
    }

    public function test_login_screen_redirects_when_already_authenticated(): void
    {
        $user = AdminCompany::factory()->create();

        $response = $this->actingAs($user, 'admin-company')
            ->get(route('operator-perusahaan.login'));

        $response->assertRedirect(route('operator-perusahaan.dashboard'));
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = AdminCompany::factory()->create();

        $response = $this->post(route('operator-perusahaan.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('admin-company');
        $response->assertRedirect(route('operator-perusahaan.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = AdminCompany::factory()->create();

        $response = $this->post(route('operator-perusahaan.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('admin-company');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_users_can_logout(): void
    {
        $user = AdminCompany::factory()->create();

        $response = $this->actingAs($user, 'admin-company')
            ->post('/logout-perusahaan');

        $this->assertGuest('admin-company');
        $response->assertRedirect(route('landing.home'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('operator-perusahaan.dashboard'));

        $response->assertRedirect(route('operator-perusahaan.login'));
    }

    public function test_users_cannot_authenticate_when_inactive(): void
    {
        $user = AdminCompany::factory()->inactive()->create();

        $response = $this->post(route('operator-perusahaan.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin-company');
        $response->assertSessionHasErrors(['email' => 'Akun anda dinonaktifkan. Hubungi admin.']);
    }
}

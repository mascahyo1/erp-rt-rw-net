<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;


{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('employee.login'));

        $response->assertOk();
    }

    public function test_login_screen_redirects_when_already_authenticated(): void
    {
        $user = Employee::factory()->create();

        $response = $this->actingAs($user, 'employee')
            ->get(route('employee.login'));

        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = Employee::factory()->create();

        $response = $this->post(route('employee.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated('employee');
        $response->assertRedirect(route('employee.dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = Employee::factory()->create();

        $response = $this->post(route('employee.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('employee');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_users_can_logout(): void
    {
        $user = Employee::factory()->create();

        $response = $this->actingAs($user, 'employee')
            ->post('/logout-karyawan');

        $this->assertGuest('employee');
        $response->assertRedirect(route('landing.home'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('employee.dashboard'));

        $response->assertRedirect(route('employee.login'));
    }

    public function test_users_cannot_authenticate_when_inactive(): void
    {
        $user = Employee::factory()->inactive()->create();

        $response = $this->post(route('employee.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('employee');
        $response->assertSessionHasErrors(['email' => 'Akun anda dinonaktifkan. Hubungi admin.']);
    }
}

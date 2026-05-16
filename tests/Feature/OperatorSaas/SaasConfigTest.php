<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\SaasConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaasConfigTest extends TestCase
{
    use RefreshDatabase;

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_saas_config_page()
    {
        $this->get('/operator-saas/konfigurasi')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_saas_config_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/konfigurasi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Konfigurasi')
                ->has('configs')
            );
    }

    public function test_can_create_saas_config()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/konfigurasi', [
            'key' => 'app_name',
            'type' => 'text',
            'value' => 'ERP RT RW Net',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Konfigurasi berhasil ditambahkan.');
        $this->assertDatabaseHas('saas_configs', [
            'key' => 'app_name',
            'type' => 'text',
            'value' => 'ERP RT RW Net',
        ]);
    }

    public function test_create_saas_config_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/konfigurasi', [
            'key' => '',
            'type' => '',
            'value' => '',
        ]);

        $response->assertSessionHasErrors(['key', 'type', 'value']);
    }

    public function test_can_update_saas_config()
    {
        $this->actingAs($this->user, 'web');
        $config = SaasConfig::create([
            'key' => 'old_key',
            'type' => 'text',
            'value' => 'Old Value',
        ]);

        $response = $this->put("/operator-saas/konfigurasi/{$config->id}", [
            'key' => 'updated_key',
            'type' => 'text',
            'value' => 'Updated Value',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Konfigurasi berhasil diperbarui.');
        $this->assertDatabaseHas('saas_configs', [
            'id' => $config->id,
            'key' => 'updated_key',
            'value' => 'Updated Value',
        ]);
    }

    public function test_can_delete_saas_config()
    {
        $this->actingAs($this->user, 'web');
        $config = SaasConfig::create([
            'key' => 'delete_key',
            'type' => 'text',
            'value' => 'Delete Value',
        ]);

        $response = $this->delete("/operator-saas/konfigurasi/{$config->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Konfigurasi berhasil dihapus.');
        $this->assertDatabaseMissing('saas_configs', ['id' => $config->id]);
    }

    public function test_can_bulk_delete_saas_config()
    {
        $this->actingAs($this->user, 'web');
        $configs = collect(range(1, 3))->map(fn ($i) => SaasConfig::create([
            'key' => "bulk_key_{$i}",
            'type' => 'text',
            'value' => "Bulk Value {$i}",
        ]));

        $response = $this->post('/operator-saas/konfigurasi/bulk-delete', [
            'ids' => $configs->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 konfigurasi berhasil dihapus.');
        foreach ($configs as $c) {
            $this->assertDatabaseMissing('saas_configs', ['id' => $c->id]);
        }
    }

    public function test_search_filter_filters_by_key_value()
    {
        $this->actingAs($this->user, 'web');
        SaasConfig::create(['key' => 'app_name', 'type' => 'text', 'value' => 'Alpha App']);
        SaasConfig::create(['key' => 'app_url', 'type' => 'text', 'value' => 'Beta URL']);
        SaasConfig::create(['key' => 'app_desc', 'type' => 'text', 'value' => 'Gamma Desc']);

        $response = $this->get('/operator-saas/konfigurasi?search=Beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Konfigurasi')
                ->has('configs.data', 1)
            );
    }
}

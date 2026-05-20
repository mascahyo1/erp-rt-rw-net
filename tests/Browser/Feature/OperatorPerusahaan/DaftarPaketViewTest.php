<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketViewTest extends DuskTestCase
{
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    private function createUserWithPerms(array $permNames): AdminCompany
    {
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'scope' => 'admin_perusahaan',
            'name' => 'Test Role ' . Str::random(6),
            'is_active' => true,
            'display_order' => 1,
        ]);
        self::$cleanupRoleIds[] = $role->id;

        $permIds = Permission::whereIn('name', $permNames)->pluck('id');
        foreach ($permIds as $pId) {
            \DB::table('role_permissions')->insert([
                'id' => (string) Str::uuid(),
                'role_id' => $role->id,
                'permission_id' => $pId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = AdminCompany::factory()->create([
            'name' => 'Test User ' . Str::random(6),
            'email' => 'test.' . Str::random(6) . '@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        \DB::table('model_has_roles')->insert([
            'id' => (string) Str::uuid(),
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => AdminCompany::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    public function test_01_page_renders(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->assertSee('Paket Customer')
                ->assertPresent('nav')
                ->assertPresent('table')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertSee('Tambah Paket')
                ->assertSee('Import')
                ->assertSee('Export')
                ->pause(500)
                ->screenshot('operator-perusahaan/daftar-paket/01-page');
        });
    }

    public function test_02_columns_visible(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        $pkg = \App\Models\InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'TEST01', 'name' => 'Paket Test', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user, $pkg) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->assertSee('Nama Paket')
                ->assertSee('Harga')
                ->assertSee('Speed')
                ->assertSee('Quota')
                ->assertSee('Billing')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertSee('Status')
                ->assertSee('TEST01')
                ->assertDontSee('Tgl')
                ->screenshot('operator-perusahaan/daftar-paket/02-columns');
        });
    }

    public static function tearDownAfterClass(): void
    {
        \DB::table('model_has_roles')->whereIn('model_id', self::$cleanupUserIds)->delete();
        AdminCompany::whereIn('id', self::$cleanupUserIds)->forceDelete();
        \DB::table('role_permissions')->whereIn('role_id', self::$cleanupRoleIds)->delete();
        Role::whereIn('id', self::$cleanupRoleIds)->delete();
        parent::tearDownAfterClass();
    }
}
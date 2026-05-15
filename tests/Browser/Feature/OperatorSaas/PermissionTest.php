<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PermissionTest extends DuskTestCase
{
    private AdminSaas $userWithAllPerms;
    private AdminSaas $userNoPerms;
    private static array $cleanupAdminIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->userWithAllPerms = AdminSaas::first();

        $this->userNoPerms = AdminSaas::factory()->create([
            'name' => 'No Permission User',
            'email' => 'noperms@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupAdminIds[] = $this->userNoPerms->id;
    }

    public function test_01_user_with_all_perms_sees_all_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userWithAllPerms, 'web')
                ->visit('/operator-saas/dashboard')
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_01/01-sidebar-full');

            $sidebarTexts = [
                'Dashboard',
                'Admin Perusahaan',
                'Perusahaan',
                'Role Perusahaan',
                'Role Admin Perusahaan',
                'Konfigurasi',
                'Role SaaS',
                'Admin SaaS',
                'Admin Role SaaS',
            ];

            foreach ($sidebarTexts as $text) {
                $browser->assertSee($text);
            }
        });
    }

    public function test_02_user_with_no_perms_sees_only_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'web')
                ->visit('/operator-saas/dashboard')
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_02/01-sidebar-limited');

            $browser->assertSee('Dashboard');

            $hiddenItems = [
                'Admin Perusahaan',
                'Perusahaan',
                'Role Perusahaan',
                'Role Admin Perusahaan',
                'Konfigurasi',
                'Role SaaS',
                'Admin SaaS',
                'Admin Role SaaS',
            ];

            foreach ($hiddenItems as $item) {
                $browser->assertDontSee($item);
            }

            $browser->visit('/operator-saas/admin-saas')
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_02/02-forbidden')
                ->assertSee('403');
        });
    }

    public function test_03_user_with_no_perms_cannot_access_create_via_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'web')
                ->visit('/operator-saas/admin-saas')
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_03/01-forbidden-page')
                ->assertSee('403');
        });
    }

    public function test_04_user_with_no_perms_cannot_access_detail_via_url(): void
    {
        $this->browse(function (Browser $browser) {
            $uuid = (string) Str::uuid();

            $browser->loginAs($this->userNoPerms, 'web')
                ->visit('/operator-saas/admin-saas/' . $uuid)
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_04/01-forbidden-detail')
                ->assertSee('403');
        });
    }

    public function test_05_sidebar_per_items_show_correctly(): void
    {
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'scope' => 'operator_saas',
            'name' => 'Limited Admin',
            'is_active' => true,
            'display_order' => 1,
        ]);
        self::$cleanupAdminIds[] = $role->id;

        $permNames = ['admin-saas.list', 'perusahaan.list'];
        $permIds = Permission::whereIn('name', $permNames)->pluck('id');

        \DB::table('role_permissions')->where('role_id', $role->id)->delete();
        foreach ($permIds as $pId) {
            \DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $pId,
            ]);
        }

        $testUser = AdminSaas::factory()->create([
            'name' => 'Limited Perm User',
            'email' => 'limitedperm@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupAdminIds[] = $testUser->id;

        \DB::table('model_has_roles')->insert([
            'id' => (string) Str::uuid(),
            'role_id' => $role->id,
            'model_id' => $testUser->id,
            'model_type' => AdminSaas::class,
        ]);

        $this->browse(function (Browser $browser) use ($testUser) {
            $browser->loginAs($testUser, 'web')
                ->visit('/operator-saas/dashboard')
                ->pause(1000)
                ->screenshot('operator-saas/permission/test_05/01-sidebar-partial');

            $visibleItems = ['Dashboard', 'Admin SaaS', 'Perusahaan'];
            foreach ($visibleItems as $item) {
                $browser->assertSee($item);
            }

            $hiddenItems = [
                'Admin Perusahaan',
                'Role Perusahaan',
                'Role Admin Perusahaan',
                'Konfigurasi',
                'Role SaaS',
                'Admin Role SaaS',
            ];
            foreach ($hiddenItems as $item) {
                $browser->assertDontSee($item);
            }
        });
    }

    public static function tearDownAfterClass(): void
    {
        if (! empty(self::$cleanupAdminIds)) {
            AdminSaas::whereIn('id', array_filter(self::$cleanupAdminIds, 'is_string'))->forceDelete();
        }

        parent::tearDownAfterClass();
    }
}

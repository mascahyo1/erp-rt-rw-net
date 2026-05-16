<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Granular RBAC test: setiap permission diuji terpisah.
 *
 * Module target: Insentif Saya (karyawan-insentif.{list,create,edit,detail,delete,restore})
 */
class InsentifPermissionTest extends DuskTestCase
{
    private Employee $superUser;
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->superUser = Employee::first();
    }

    private function createEmployeeWithPerms(array $permNames): Employee
    {
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'scope' => 'karyawan_perusahaan',
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

        $user = Employee::factory()->create([
            'name' => 'Test Kary ' . Str::random(6),
            'email' => 'test.' . Str::random(6) . '@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        \DB::table('model_has_roles')->insert([
            'id' => (string) Str::uuid(),
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => Employee::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    // ============================================================
    // LIST ONLY
    // ============================================================
    public function test_01_list_only_sees_sidebar_and_table_no_action_buttons(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/01-list/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Insentif Saya')
                ->assertDontSee('Tagihan');

            $browser->visit('/karyawan/insentif-saya?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/01-list/02-table');

            $browser->assertPresent('table');
            $browser->assertDontSee('Tambah');
            $browser->assertDontSee('data dipilih');
            $browser->assertDontSee('Edit');
            $browser->assertDontSee('Hapus');

            $browser->visit('/karyawan/insentif-saya/create')
                ->pause(500)
                ->assertSee('403');
        });
    }

    // ============================================================
    // LIST + CREATE
    // ============================================================
    public function test_02_list_plus_create_shows_add_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list', 'karyawan-insentif.create']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/insentif-saya?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/02-create/01-before');

            $browser->assertSee('Tambah');
            $browser->assertDontSee('Hapus');

            $browser->press('Tambah')
                ->pause(500)
                ->screenshot('karyawan/insentif-permission/02-create/02-modal')
                ->assertSee('Tambah Insentif');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + EDIT
    // ============================================================
    public function test_03_list_plus_edit_shows_edit_button_and_bulk_toggle(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list', 'karyawan-insentif.edit']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/insentif-saya?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/03-edit/01-table');

            $browser->assertDontSee('Tambah');
            $browser->assertPresent('a[title="Edit"], button[title="Edit"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/03-edit/02-bulk-active');

            $browser->assertDontSee('Hapus');

            $browser->script("document.querySelector('a[title=\"Edit\"], button[title=\"Edit\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/03-edit/03-modal')
                ->assertSee('Edit Insentif');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DETAIL
    // ============================================================
    public function test_04_list_plus_detail_shows_detail_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list', 'karyawan-insentif.detail']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/insentif-saya?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/04-detail/01-table');

            $browser->assertPresent('a[title="Detail"], button[title="Detail"]');

            $browser->script("document.querySelector('a[title=\"Detail\"], button[title=\"Detail\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/04-detail/02-modal')
                ->assertSee('Detail Insentif');

            $browser->press('Tutup')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DELETE
    // ============================================================
    public function test_05_list_plus_delete_shows_delete_button_and_bulk_delete(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list', 'karyawan-insentif.delete']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/insentif-saya?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/05-delete/01-table');

            $browser->assertPresent('button[title="Hapus"], a[title="Hapus"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/05-delete/02-bulk-delete');

            $browser->assertSee('data dipilih');

            $browser->script("document.querySelector('button[title=\"Hapus\"], a[title=\"Hapus\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/05-delete/03-delete-modal')
                ->assertSee('Hapus Insentif?');
        });
    }

    // ============================================================
    // LIST + RESTORE
    // ============================================================
    public function test_06_list_plus_restore_shows_restore_and_bulk_restore_in_trash(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-insentif.list', 'karyawan-insentif.restore']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/insentif-saya?terhapus=ya&per_page=100')
                ->pause(800)
                ->screenshot('karyawan/insentif-permission/06-restore/01-trash-tab');

            $browser->assertPresent('table');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/insentif-permission/06-restore/02-bulk-restore');

            $browser->assertSee('Pulihkan');
        });
    }

    // ============================================================
    // NO PERMISSION → FORBIDDEN ALL
    // ============================================================
    public function test_07_no_perms_all_routes_forbidden(): void
    {
        $user = Employee::factory()->create([
            'name' => 'Zero Perm Kary',
            'email' => 'zeroperm.ins@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee');

            $browser->visit('/karyawan/dashboard')
                ->pause(500)
                ->screenshot('karyawan/insentif-permission/07-forbidden/01-sidebar-only-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Insentif Saya');

            $browser->visit('/karyawan/insentif-saya')
                ->pause(500)
                ->screenshot('karyawan/insentif-permission/07-forbidden/02-get-forbidden')
                ->assertSee('403');
        });
    }

    public static function tearDownAfterClass(): void
    {
        Employee::whereIn('id', self::$cleanupUserIds)->forceDelete();
        Role::whereIn('id', self::$cleanupRoleIds)->forceDelete();
        parent::tearDownAfterClass();
    }
}

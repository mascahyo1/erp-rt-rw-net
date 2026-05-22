<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Granular RBAC test: setiap permission diuji terpisah.
 *
 * Module target: Admin Perusahaan (admin-perusahaan.{list,create,edit,detail,delete,restore})
 *
 * | Permission | tanpa permission |
 * |-----------|-----------------|
 * | list      | sidebar ga muncul, URL get → 403 |
 * | create    | tombol tambah ga muncul, URL post → 403 |
 * | edit      | tombol edit ga muncul, bulk toggle ga muncul, URL put → 403 |
 * | detail    | tombol detail ga muncul |
 * | delete    | tombol hapus ga muncul, bulk delete ga muncul, URL delete → 403 |
 * | restore   | tombol pulihkan ga muncul, bulk restore ga muncul, URL post restore → 403 |
 */
class AdminPerusahaanPermissionTest extends DuskTestCase
{
    private AdminCompany $superAdmin;
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = AdminCompany::first();
    }

    /** Helper: create user with specific permission names */
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

    // ============================================================
    // LIST ONLY
    // ============================================================
    public function test_01_list_only_sees_sidebar_and_table_no_action_buttons(): void
    {
        AdminCompany::factory()->count(3)->create(['is_active' => true]);

        $user = $this->createUserWithPerms(['admin-perusahaan.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/01-list/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Admin Perusahaan');

            $browser->visit('/operator-perusahaan/admin-perusahaan?per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/01-list/02-table');

            // Table visible
            $browser->assertPresent('table');
            // No tambah button
            $browser->assertDontSee('Tambah');
            // No bulk bar because no edit/delete/restore
            $browser->assertDontSee('Aktifkan');
            $browser->assertDontSee('Nonaktifkan');
            $browser->assertDontSee('data dipilih');
            // No edit/delete/detail/restore button per row
            $browser->assertDontSee('Edit');
            $browser->assertDontSee('Hapus');
            $browser->assertDontSee('Detail');

            // Direct POST store → 403
            $browser->visit('/operator-perusahaan/admin-perusahaan/create')
                ->pause(500)
                ->assertSee('403');
        });
    }

    // ============================================================
    // LIST + CREATE
    // ============================================================
    public function test_02_list_plus_create_shows_add_button_and_can_open_modal(): void
    {
        $user = $this->createUserWithPerms(['admin-perusahaan.list', 'admin-perusahaan.create']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-perusahaan?per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/02-create/01-before');

            // Tambah button visible
            $browser->assertSee('Tambah');
            // No edit/delete buttons (missing edit, delete perms)
            $browser->assertDontSee('Aktifkan');
            $browser->assertDontSee('Hapus');

            // Click Tambah → modal opens
            $browser->press('Tambah')
                ->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/02-create/02-modal')
                ->assertSee('Tambah Admin Perusahaan');

            // Close modal
            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + EDIT
    // ============================================================
    public function test_03_list_plus_edit_shows_edit_button_and_bulk_toggle(): void
    {
        $user = $this->createUserWithPerms(['admin-perusahaan.list', 'admin-perusahaan.edit']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-perusahaan?per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/03-edit/01-table');

            // No tambah button (missing create)
            $browser->assertDontSee('Tambah');
            // Edit button must be present
            $browser->assertPresent('button[title="Edit"]');

            // Select first checkbox to trigger bulk bar
            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/03-edit/02-bulk-active');

            // Bulk toggle buttons visible (nebeng .edit)
            $browser->assertSee('Aktifkan');
            $browser->assertSee('Nonaktifkan');
            // Bulk delete NOT visible (missing .delete)
            $browser->assertDontSee('Hapus');

            // Click edit button on first row → modal opens
            $browser->script("document.querySelector('button[title=\"Edit\"]').click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/03-edit/03-modal')
                ->assertSee('Edit Admin Perusahaan');

            // Close modal
            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DETAIL
    // ============================================================
    public function test_04_list_plus_detail_shows_detail_button_and_can_open_modal(): void
    {
        $user = $this->createUserWithPerms(['admin-perusahaan.list', 'admin-perusahaan.detail']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-perusahaan?per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/04-detail/01-table');

            // Detail button visible
            $browser->assertPresent('button[title="Detail"]');

            // Click detail button on first row
            $browser->script("document.querySelector('button[title=\"Detail\"]').click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/04-detail/02-modal')
                ->assertSee('Detail Admin');

            $browser->press('Tutup')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DELETE
    // ============================================================
    public function test_05_list_plus_delete_shows_delete_button_and_bulk_delete(): void
    {
        $user = $this->createUserWithPerms(['admin-perusahaan.list', 'admin-perusahaan.delete']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-perusahaan?per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/05-delete/01-table');

            // Delete button visible per row
            $browser->assertPresent('button[title="Hapus"]');

            // Select checkbox to trigger bulk
            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/05-delete/02-bulk-delete');

            // Bulk hapus visible (nebeng .delete)
            $browser->assertSee('data dipilih');
            $browser->assertDontSee('Aktifkan');  // no .edit

            // Click delete button → modal konfirmasi
            $browser->script("document.querySelector('button[title=\"Hapus\"]').click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/05-delete/03-delete-modal')
                ->assertSee('Hapus Admin?');
        });
    }

    // ============================================================
    // LIST + RESTORE
    // ============================================================
    public function test_06_list_plus_restore_shows_restore_and_bulk_restore_in_trash(): void
    {
        $user = $this->createUserWithPerms(['admin-perusahaan.list', 'admin-perusahaan.restore']);

        // Create + soft-delete a record so trash tab has data
        $trashRecord = AdminCompany::factory()->create([
            'name' => 'To Restore Admin',
            'is_active' => true,
        ]);
        $trashRecord->delete();
        self::$cleanupUserIds[] = $trashRecord->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-perusahaan?terhapus=ya&per_page=100')
                ->pause(800)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/06-restore/01-trash-tab');

            // Restore button visible per row
            $browser->assertPresent('button[title="Pulihkan"]');

            // Select checkbox
            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/06-restore/02-bulk-restore');

            // Bulk restore visible (nebeng .restore)
            $browser->assertSee('Pulihkan');
        });
    }

    // ============================================================
    // NO PERMISSION → FORBIDDEN ALL
    // ============================================================
    public function test_07_no_perms_all_routes_forbidden(): void
    {
        $user = AdminCompany::factory()->create([
            'name' => 'Zero Perm User',
            'email' => 'zeroperm@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company');

            // Sidebar should only show Dashboard
            $browser->visit('/operator-perusahaan/dashboard')
                ->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/07-forbidden/01-sidebar-only-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Admin Perusahaan');

            // Direct GET → 403
            $browser->visit('/operator-perusahaan/admin-perusahaan')
                ->pause(500)
                ->screenshot('operator-perusahaan/adminperusahaan-permission/07-forbidden/02-get-forbidden')
                ->assertSee('403');
        });
    }

    public static function tearDownAfterClass(): void
    {
        AdminCompany::whereIn('id', self::$cleanupUserIds)->forceDelete();
        Role::whereIn('id', self::$cleanupRoleIds)->forceDelete();
        parent::tearDownAfterClass();
    }
}

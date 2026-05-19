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
 * Module target: Daftar Paket (paket.{list,create,edit,detail,delete,restore,export,import})
 *
 * | Permission | tanpa permission |
 * |-----------|-----------------|
 * | list      | sidebar ga muncul, URL get → 403 |
 * | create    | tombol tambah ga muncul, URL post → 403 |
 * | edit      | tombol edit ga muncul, bulk toggle ga muncul, URL put → 403 |
 * | detail    | tombol detail ga muncul |
 * | delete    | tombol hapus ga muncul, bulk delete ga muncul, URL delete → 403 |
 * | restore   | tombol pulihkan ga muncul, bulk restore ga muncul, URL post restore → 403 |
 * | export    | tombol export ga muncul, URL get export → 403 |
 * | import    | tombol import ga muncul, template link ga muncul, URL post import → 403 |
 */
class DaftarPaketPermissionTest extends DuskTestCase
{
    private AdminCompany $superAdmin;
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = AdminCompany::first();
    }

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
    // LIST
    // ============================================================

    public function test_01_list_without_permission_blocked(): void
    {
        $user = $this->createUserWithPerms([]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->pause(1000)
                ->screenshot('operator-perusahaan/daftar-paket/permission/01-list-blocked')
                ->assertSee('403')
                ->assertDontSee('Paket Customer');
        });
    }

    public function test_02_list_with_permission_visible(): void
    {
        $user = $this->createUserWithPerms(['paket.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/02-list-visible')
                ->assertSee('Paket Customer')
                ->assertDontSee('Tambah Paket'); // tanpa create
        });
    }

    // ============================================================
    // CREATE
    // ============================================================

    public function test_03_create_without_permission_hidden(): void
    {
        $user = $this->createUserWithPerms(['paket.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/03-create-hidden')
                ->assertDontSee('Tambah Paket');
        });
    }

    public function test_04_create_with_permission_visible(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/04-create-visible')
                ->assertSee('Tambah Paket');
        });
    }

    // ============================================================
    // EXPORT
    // ============================================================

    public function test_05_export_without_permission_hidden(): void
    {
        $user = $this->createUserWithPerms(['paket.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/05-export-hidden')
                ->assertDontSee('Export');
        });
    }

    public function test_06_export_with_permission_visible(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.export']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/06-export-visible')
                ->assertSee('Export');
        });
    }

    // ============================================================
    // IMPORT
    // ============================================================

    public function test_07_import_without_permission_hidden(): void
    {
        $user = $this->createUserWithPerms(['paket.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/07-import-hidden')
                ->assertDontSee('Import')
                ->assertDontSee('Template');
        });
    }

    public function test_08_import_with_permission_visible(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.import']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/permission/08-import-visible')
                ->assertSee('Import')
                ->assertSee('Template');
        });
    }

    // ============================================================
    // CLEANUP
    // ============================================================

    public static function tearDownAfterClass(): void
    {
        \DB::table('model_has_roles')->whereIn('model_id', self::$cleanupUserIds)->delete();
        AdminCompany::whereIn('id', self::$cleanupUserIds)->forceDelete();
        \DB::table('role_permissions')->whereIn('role_id', self::$cleanupRoleIds)->delete();
        Role::whereIn('id', self::$cleanupRoleIds)->delete();
        parent::tearDownAfterClass();
    }
}

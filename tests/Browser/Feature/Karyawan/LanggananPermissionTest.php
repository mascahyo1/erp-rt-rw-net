<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Customer;
use App\Models\CustInternet;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Granular RBAC test: setiap permission diuji terpisah.
 *
 * Module target: Langganan Customer (karyawan-langganan.{list,create,edit,detail,delete,restore})
 */
class LanggananPermissionTest extends DuskTestCase
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

    private function seedTableData(Employee $user, int $count = 3): Customer
    {
        $customer = Customer::factory()->create(['company_id' => $user->company_id]);

        for ($i = 0; $i < $count; $i++) {
            CustInternet::create([
                'customer_id' => $customer->id,
                'internet_status' => 'active',
            ]);
        }

        return $customer;
    }

    // ============================================================
    // LIST ONLY
    // ============================================================
    public function test_01_list_only_sees_sidebar_and_table_no_action_buttons(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/01-list/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Langganan Customer')
                ->assertDontSee('Tagihan');

            $browser->visit('/karyawan/langganan-customer?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/01-list/02-table');

            $browser->assertPresent('table');
            $browser->assertDontSee('Tambah');
            $browser->assertDontSee('data dipilih');
            $browser->assertDontSee('Edit');
            $browser->assertDontSee('Hapus');

            $browser->visit('/karyawan/langganan-customer/create')
                ->pause(500)
                ->assertSee('403');
        });
    }

    // ============================================================
    // LIST + CREATE
    // ============================================================
    public function test_02_list_plus_create_shows_add_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list', 'karyawan-langganan.create']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/langganan-customer?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/02-create/01-before');

            $browser->assertSee('Tambah');
            $browser->assertDontSee('Hapus');

            $browser->press('Tambah')
                ->pause(500)
                ->screenshot('karyawan/langganan-permission/02-create/02-modal')
                ->assertSee('Tambah Langganan');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + EDIT
    // ============================================================
    public function test_03_list_plus_edit_shows_edit_button_and_bulk_toggle(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list', 'karyawan-langganan.edit']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/langganan-customer?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/03-edit/01-table');

            $browser->assertDontSee('Tambah');
            $browser->assertPresent('a[title="Edit"], button[title="Edit"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/03-edit/02-bulk-active');

            $browser->assertDontSee('Hapus');

            $browser->script("document.querySelector('a[title=\"Edit\"], button[title=\"Edit\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/03-edit/03-modal')
                ->assertSee('Edit Langganan');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DETAIL
    // ============================================================
    public function test_04_list_plus_detail_shows_detail_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list', 'karyawan-langganan.detail']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/langganan-customer?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/04-detail/01-table');

            $browser->assertPresent('a[title="Detail"], button[title="Detail"]');

            $browser->script("document.querySelector('a[title=\"Detail\"], button[title=\"Detail\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/04-detail/02-modal')
                ->assertSee('Detail Langganan');

            $browser->press('Tutup')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DELETE
    // ============================================================
    public function test_05_list_plus_delete_shows_delete_button_and_bulk_delete(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list', 'karyawan-langganan.delete']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/langganan-customer?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/05-delete/01-table');

            $browser->assertPresent('button[title="Hapus"], a[title="Hapus"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/05-delete/02-bulk-delete');

            $browser->assertSee('data dipilih');

            $browser->script("document.querySelector('button[title=\"Hapus\"], a[title=\"Hapus\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/05-delete/03-delete-modal')
                ->assertSee('Hapus Langganan?');
        });
    }

    // ============================================================
    // LIST + RESTORE
    // ============================================================
    public function test_06_list_plus_restore_shows_restore_and_bulk_restore_in_trash(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-langganan.list', 'karyawan-langganan.restore']);

        $customer = Customer::factory()->create(['company_id' => $user->company_id]);
        $trashLangganan = CustInternet::create([
            'customer_id' => $customer->id,
            'internet_status' => 'active',
        ]);
        $trashLangganan->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/langganan-customer?terhapus=ya&per_page=100')
                ->pause(800)
                ->screenshot('karyawan/langganan-permission/06-restore/01-trash-tab');

            $browser->assertPresent('button[title="Pulihkan"], a[title="Pulihkan"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/langganan-permission/06-restore/02-bulk-restore');

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
            'email' => 'zeroperm.lan@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee');

            $browser->visit('/karyawan/dashboard')
                ->pause(500)
                ->screenshot('karyawan/langganan-permission/07-forbidden/01-sidebar-only-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Langganan Customer');

            $browser->visit('/karyawan/langganan-customer')
                ->pause(500)
                ->screenshot('karyawan/langganan-permission/07-forbidden/02-get-forbidden')
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

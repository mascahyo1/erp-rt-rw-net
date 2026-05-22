<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Customer;
use App\Models\CustInternet;
use App\Models\CustInternetInvc;
use App\Models\CustInternetPayment;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Granular RBAC test: setiap permission diuji terpisah.
 *
 * Module target: Riwayat Pembayaran (karyawan-riwayat-pembayaran.{list,create,edit,detail,delete,restore})
 */
class RiwayatPembayaranPermissionTest extends DuskTestCase
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

        $custInternet = CustInternet::create([
            'customer_id' => $customer->id,
            'internet_status' => 'active',
            'billing_amount' => 250000,
        ]);

        $invc = CustInternetInvc::create([
            'cust_internet_id' => $custInternet->id,
            'invoice_number' => 'INV-' . now()->format('Ym') . '-001',
            'amount' => 250000,
            'status' => 'Lunas',
            'invoice_due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        for ($i = 0; $i < $count; $i++) {
            CustInternetPayment::create([
                'cust_internet_invc_id' => $invc->id,
                'amount_paid' => 250000,
                'payment_method' => 'transfer',
                'status' => 'success',
            ]);
        }

        return $customer;
    }

    // ============================================================
    // LIST ONLY
    // ============================================================
    public function test_01_list_only_sees_sidebar_and_table_no_action_buttons(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/01-list/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Riwayat Pembayaran')
                ->assertDontSee('Tagihan');

            $browser->visit('/karyawan/riwayat-pembayaran?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/01-list/02-table');

            $browser->assertPresent('table');
            $browser->assertDontSee('Tambah');
            $browser->assertDontSee('data dipilih');
            $browser->assertDontSee('Edit');
            $browser->assertDontSee('Hapus');

            $browser->visit('/karyawan/riwayat-pembayaran/create')
                ->pause(500)
                ->assertSee('403');
        });
    }

    // ============================================================
    // LIST + CREATE
    // ============================================================
    public function test_02_list_plus_create_shows_add_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list', 'karyawan-riwayat-pembayaran.create']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/riwayat-pembayaran?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/02-create/01-before');

            $browser->assertSee('Tambah');
            $browser->assertDontSee('Hapus');

            $browser->press('Tambah')
                ->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/02-create/02-modal')
                ->assertSee('Tambah Riwayat Pembayaran');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + EDIT
    // ============================================================
    public function test_03_list_plus_edit_shows_edit_button_and_bulk_toggle(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list', 'karyawan-riwayat-pembayaran.edit']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/riwayat-pembayaran?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/03-edit/01-table');

            $browser->assertDontSee('Tambah');
            $browser->assertPresent('a[title="Edit"], button[title="Edit"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/03-edit/02-bulk-active');

            $browser->assertDontSee('Hapus');

            $browser->script("document.querySelector('a[title=\"Edit\"], button[title=\"Edit\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/03-edit/03-modal')
                ->assertSee('Edit Riwayat Pembayaran');

            $browser->press('Batal')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DETAIL
    // ============================================================
    public function test_04_list_plus_detail_shows_detail_button_and_can_open_modal(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list', 'karyawan-riwayat-pembayaran.detail']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/riwayat-pembayaran?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/04-detail/01-table');

            $browser->assertPresent('a[title="Detail"], button[title="Detail"]');

            $browser->script("document.querySelector('a[title=\"Detail\"], button[title=\"Detail\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/04-detail/02-modal')
                ->assertSee('Detail Riwayat Pembayaran');

            $browser->press('Tutup')
                ->pause(300);
        });
    }

    // ============================================================
    // LIST + DELETE
    // ============================================================
    public function test_05_list_plus_delete_shows_delete_button_and_bulk_delete(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list', 'karyawan-riwayat-pembayaran.delete']);
        $this->seedTableData($user);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/riwayat-pembayaran?per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/05-delete/01-table');

            $browser->assertPresent('button[title="Hapus"], a[title="Hapus"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/05-delete/02-bulk-delete');

            $browser->assertSee('data dipilih');

            $browser->script("document.querySelector('button[title=\"Hapus\"], a[title=\"Hapus\"]').click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/05-delete/03-delete-modal')
                ->assertSee('Hapus Riwayat Pembayaran?');
        });
    }

    // ============================================================
    // LIST + RESTORE
    // ============================================================
    public function test_06_list_plus_restore_shows_restore_and_bulk_restore_in_trash(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-riwayat-pembayaran.list', 'karyawan-riwayat-pembayaran.restore']);

        $customer = Customer::factory()->create(['company_id' => $user->company_id]);
        $custInternet = CustInternet::create([
            'customer_id' => $customer->id,
            'internet_status' => 'active',
            'billing_amount' => 250000,
        ]);
        $invc = CustInternetInvc::create([
            'cust_internet_id' => $custInternet->id,
            'invoice_number' => 'INV-DEL-RP-001',
            'amount' => 250000,
            'status' => 'Lunas',
            'invoice_due_date' => now()->addDays(30)->format('Y-m-d'),
        ]);
        $trashPayment = CustInternetPayment::create([
            'cust_internet_invc_id' => $invc->id,
            'amount_paid' => 250000,
            'payment_method' => 'transfer',
            'status' => 'success',
        ]);
        $trashPayment->delete();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/riwayat-pembayaran?terhapus=ya&per_page=100')
                ->pause(800)
                ->screenshot('karyawan/riwayat-pembayaran-permission/06-restore/01-trash-tab');

            $browser->assertPresent('button[title="Pulihkan"], a[title="Pulihkan"]');

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/06-restore/02-bulk-restore');

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
            'email' => 'zeroperm.rp@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee');

            $browser->visit('/karyawan/dashboard')
                ->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/07-forbidden/01-sidebar-only-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Riwayat Pembayaran');

            $browser->visit('/karyawan/riwayat-pembayaran')
                ->pause(500)
                ->screenshot('karyawan/riwayat-pembayaran-permission/07-forbidden/02-get-forbidden')
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

<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Granular RBAC test: karyawan portal bersifat VIEW-ONLY.
 *
 * Permission keys (semua .list):
 * - profil-saya.list
 * - karyawan-customer.list
 * - karyawan-langganan.list
 * - karyawan-tagihan.list
 * - karyawan-insentif.list
 * - karyawan-riwayat-pembayaran.list
 *
 * | Permission | tanpa permission |
 * |------------|-----------------|
 * | *.list     | sidebar ga muncul, URL get → 403 |
 */
class PermissionTest extends DuskTestCase
{
    private Employee $superUser;
    private static array $cleanupUserIds = [];
    private static array $cleanupRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->superUser = Employee::first();
    }

    /** Helper: create employee with specific permission names */
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

        $employee = Employee::factory()->create([
            'name' => 'Test Karyawan ' . Str::random(6),
            'email' => 'test.' . Str::random(6) . '@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $employee->id;

        \DB::table('model_has_roles')->insert([
            'id' => (string) Str::uuid(),
            'role_id' => $role->id,
            'model_id' => $employee->id,
            'model_type' => Employee::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $employee;
    }

    // ============================================================
    // ALL PERMISSIONS → FULL SIDEBAR
    // ============================================================
    public function test_01_user_with_all_perms_sees_all_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->superUser, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/permission/01-all-sidebar/01-dashboard')
                ->assertSee('Dashboard')
                ->assertSee('Profil Saya')
                ->assertSee('Customer')
                ->assertSee('Langganan Customer')
                ->assertSee('Tagihan')
                ->assertSee('Insentif Saya')
                ->assertSee('Riwayat Pembayaran');
        });
    }

    // ============================================================
    // NO PERMISSION → DASHBOARD ONLY, REST 403
    // ============================================================
    public function test_02_user_with_no_perms_sees_only_dashboard(): void
    {
        $user = Employee::factory()->create([
            'name' => 'Zero Perm Karyawan',
            'email' => 'zeroperm-kar@rtrwnet.id',
            'is_active' => true,
        ]);
        self::$cleanupUserIds[] = $user->id;

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/permission/02-no-perms/01-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Customer')
                ->assertDontSee('Tagihan')
                ->assertDontSee('Insentif Saya');

            $browser->visit('/karyawan/customer')
                ->pause(500)
                ->screenshot('karyawan/permission/02-no-perms/02-forbidden')
                ->assertSee('403');
        });
    }

    // ============================================================
    // CUSTOMER ONLY
    // ============================================================
    public function test_03_customer_only_perms_shows_customer_sidebar(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-customer.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/permission/03-customer-only/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Customer')
                ->assertDontSee('Tagihan')
                ->assertDontSee('Insentif Saya')
                ->assertDontSee('Langganan Customer');

            $browser->visit('/karyawan/customer')
                ->pause(800)
                ->screenshot('karyawan/permission/03-customer-only/02-page')
                ->assertPresent('table');
        });
    }

    // ============================================================
    // TAGIHAN ONLY
    // ============================================================
    public function test_04_tagihan_only_perms_shows_tagihan_sidebar(): void
    {
        $user = $this->createEmployeeWithPerms(['karyawan-tagihan.list']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/permission/04-tagihan-only/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Tagihan')
                ->assertDontSee('Customer')
                ->assertDontSee('Insentif Saya');
        });
    }

    // ============================================================
    // MULTIPLE PERMS
    // ============================================================
    public function test_05_multiple_perms_shows_multiple_sidebar(): void
    {
        $user = $this->createEmployeeWithPerms([
            'karyawan-customer.list',
            'karyawan-insentif.list',
            'profil-saya.list',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'employee')
                ->visit('/karyawan/dashboard')
                ->pause(800)
                ->screenshot('karyawan/permission/05-multiple-perms/01-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Customer')
                ->assertSee('Insentif Saya')
                ->assertSee('Profil Saya')
                ->assertDontSee('Tagihan')
                ->assertDontSee('Langganan Customer')
                ->assertDontSee('Riwayat Pembayaran');
        });
    }

    // ============================================================
    // CLEANUP
    // ============================================================
    public static function tearDownAfterClass(): void
    {
        Employee::whereIn('id', self::$cleanupUserIds)->forceDelete();
        Role::whereIn('id', self::$cleanupRoleIds)->forceDelete();
        parent::tearDownAfterClass();
    }
}

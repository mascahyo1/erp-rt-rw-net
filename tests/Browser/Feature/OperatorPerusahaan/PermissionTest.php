<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\ModelHasRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PermissionTest extends DuskTestCase
{
    private AdminCompany $userWithAllPerms;
    private AdminCompany $userNoPerms;

    private static array $sidebarLabels = [
        'Dashboard',
        'Perusahaan Saya',
        'Daftar Paket',
        'Customer',
        'Langganan Customer',
        'Tagihan',
        'Insentif',
        'Riwayat Insentif',
        'Riwayat Pembayaran',
        'Admin Perusahaan',
        'Role Perusahaan',
        'Admin Role Perusahaan',
        'Karyawan',
        'Role Web Karyawan',
        'Admin Role Web Karyawan',
        'Konfigurasi Perusahaan',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->userWithAllPerms = AdminCompany::first();
        $this->userNoPerms = AdminCompany::factory()->create([
            'name' => 'No Permission OpPer',
            'email' => 'noperms-opp@rtrwnet.id',
            'is_active' => true,
        ]);
    }

    public function test_01_user_with_all_perms_sees_all_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userWithAllPerms, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->waitForText('Dashboard', 10)
                ->screenshot('operator-perusahaan/permission/01-all-perms-sidebar/01-dashboard');

            foreach (self::$sidebarLabels as $label) {
                $browser->assertSee($label);
            }

            $browser->screenshot('operator-perusahaan/permission/01-all-perms-sidebar/02-sidebar');
        });
    }

    public function test_02_user_with_no_perms_sees_only_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->waitForText('Dashboard', 10)
                ->screenshot('operator-perusahaan/permission/02-no-perms-sidebar/01-dashboard')
                ->assertSee('Dashboard')
                ->assertDontSee('Customer')
                ->assertDontSee('Tagihan')
                ->assertDontSee('Admin Perusahaan');
        });
    }

    public function test_03_user_with_no_perms_cannot_access_create_via_url(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'admin-company')
                ->visit('/operator-perusahaan/customer')
                ->pause(1000)
                ->screenshot('operator-perusahaan/permission/03-no-perms-403/01-forbidden')
                ->assertSee('403');
        });
    }

    public function test_04_limited_perms_shows_only_granted_sidebar(): void
    {
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'name' => 'Limited Test Role',
            'is_active' => true,
            'display_order' => 1,
            'description' => 'Role with customer.list and tagihan.list only',
        ]);

        $customerPerm = Permission::firstOrCreate(
            ['name' => 'customer.list', 'scope' => 'admin_perusahaan'],
            ['display_order' => 1, 'description' => 'Lihat daftar customer']
        );
        $tagihanPerm = Permission::firstOrCreate(
            ['name' => 'tagihan.list', 'scope' => 'admin_perusahaan'],
            ['display_order' => 2, 'description' => 'Lihat daftar tagihan']
        );

        \DB::table('role_permissions')->insert([
            ['id' => Str::uuid(), 'role_id' => $role->id, 'permission_id' => $customerPerm->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid(), 'role_id' => $role->id, 'permission_id' => $tagihanPerm->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $limitedUser = AdminCompany::factory()->create([
            'name' => 'Limited OpPer',
            'email' => 'limited-opp@rtrwnet.id',
            'is_active' => true,
        ]);
        ModelHasRole::create([
            'model_type' => AdminCompany::class,
            'model_id' => $limitedUser->id,
            'role_id' => $role->id,
        ]);

        $this->browse(function (Browser $browser) use ($limitedUser) {
            $browser->loginAs($limitedUser, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->waitForText('Dashboard', 10)
                ->screenshot('operator-perusahaan/permission/04-limited-perms-sidebar/01-dashboard')
                ->assertSee('Dashboard')
                ->assertSee('Customer')
                ->assertSee('Tagihan')
                ->assertDontSee('Admin Perusahaan');
        });

        \DB::table('model_has_roles')->where('role_id', $role->id)->delete();
        \DB::table('role_permissions')->where('role_id', $role->id)->delete();
        $role->delete();
        $limitedUser->delete();
    }
}

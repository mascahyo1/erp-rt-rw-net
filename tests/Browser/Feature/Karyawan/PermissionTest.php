<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Permission;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Support\Str;

class PermissionTest extends DuskTestCase
{
    private Employee $userWithAllPerms;
    private Employee $userNoPerms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userWithAllPerms = Employee::first();
        $this->userNoPerms = Employee::factory()->create([
            'name' => 'No Permission Karyawan',
            'email' => 'noperms-kar@rtrwnet.id',
            'is_active' => true,
        ]);
    }

    public function test_01_user_with_all_perms_sees_all_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userWithAllPerms, 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(500)
                ->screenshot('karyawan/permission/01-all-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Profil Saya')
                ->assertSee('Customer')
                ->assertSee('Langganan Customer')
                ->assertSee('Tagihan')
                ->assertSee('Insentif Saya')
                ->assertSee('Riwayat Pembayaran');
        });
    }

    public function test_02_user_with_no_perms_sees_only_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(500)
                ->screenshot('karyawan/permission/02-no-perms-sidebar')
                ->assertSee('Dashboard')
                ->assertDontSee('Customer');
        });
    }

    public function test_03_user_with_no_perms_gets_403_on_protected_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->userNoPerms, 'employee')
                ->visit('/karyawan/customer')
                ->waitForText('403', 10)
                ->pause(500)
                ->screenshot('karyawan/permission/03-forbidden');
        });
    }

    public function test_04_limited_perms_shows_only_granted_sidebar(): void
    {
        $limitedUser = Employee::factory()->create([
            'name' => 'Limited Karyawan',
            'email' => 'limited-kar@rtrwnet.id',
            'is_active' => true,
        ]);

        $role = Role::create([
            'id' => (string) Str::uuid(),
            'scope' => 'karyawan_perusahaan',
            'name' => 'Limited Karyawan Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $permIds = Permission::whereIn('name', ['karyawan-customer.list', 'karyawan-tagihan.list'])
            ->pluck('id');

        \DB::table('role_permissions')->where('role_id', $role->id)->delete();
        foreach ($permIds as $pId) {
            \DB::table('role_permissions')->insert([
                'id' => Str::uuid(),
                'role_id' => $role->id,
                'permission_id' => $pId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \DB::table('model_has_roles')->insert([
            'id' => Str::uuid(),
            'role_id' => $role->id,
            'model_type' => 'App\Models\Employee',
            'model_id' => $limitedUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($limitedUser) {
            $browser->loginAs($limitedUser, 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(500)
                ->screenshot('karyawan/permission/04-limited-sidebar')
                ->assertSee('Dashboard')
                ->assertSee('Customer')
                ->assertSee('Tagihan')
                ->assertDontSee('Insentif Saya');
        });
    }

    public static function tearDownAfterClass(): void
    {
        Employee::where('email', 'like', '%noperms%')
            ->orWhere('email', 'like', '%limited%')
            ->forceDelete();

        Role::where('name', 'Limited Karyawan Role')->forceDelete();

        parent::tearDownAfterClass();
    }
}

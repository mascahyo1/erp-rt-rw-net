<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\CustInternet;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketCRUDTest extends DuskTestCase
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
        InternetPackage::factory()->count(5)->create(['company_id' => $user->company_id, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Paket')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertDontSee('Tgl')
                ->screenshot('operator-perusahaan/daftar-paket/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'b10', 'name' => 'Paket Premium', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'p25', 'name' => 'Paket Basic', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->type('input[placeholder="Cari..."]', 'Premium')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/02-search/01-result')
                ->assertSee('Paket Premium')
                ->assertDontSee('Paket Basic');
        });
    }

    public function test_02b_search_by_code(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'b10', 'name' => 'Basic 10Mbps', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'p25', 'name' => 'Pro 25Mbps', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->type('input[placeholder="Cari..."]', 'b10')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/02-search/02-code-result')
                ->assertSee('b10')
                ->assertSee('Basic 10Mbps')
                ->assertDontSee('Pro 25Mbps');
        });
    }

    public function test_03_filter_status(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Paket Aktif', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Paket Nonaktif', 'is_active' => false]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            $browser->select('select:first-of-type', 'Aktif')
                ->pause(500)
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/03-filter-status/01-aktif')
                ->assertSee('Paket Aktif')
                ->assertDontSee('Paket Nonaktif');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->pause(500)
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/03-filter-status/02-nonaktif')
                ->assertSee('Paket Nonaktif')
                ->assertDontSee('Paket Aktif');
        });
    }

    public function test_04_sort(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'aaa01', 'name' => 'AAA Paket', 'price' => 100000, 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'zzz02', 'name' => 'ZZZ Paket', 'price' => 500000, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            $browser->script("var ths = document.querySelectorAll('thead th'); for(var i=0;i<ths.length;i++){ if(ths[i].textContent.includes('Nama')) ths[i].click(); }");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/01-name');

            $browser->script("var ths = document.querySelectorAll('thead th'); for(var i=0;i<ths.length;i++){ if(ths[i].textContent.includes('Harga')) ths[i].click(); }");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/02-price');

            $browser->script("var ths = document.querySelectorAll('thead th'); for(var i=0;i<ths.length;i++){ if(ths[i].textContent.includes('Estimasi')) ths[i].click(); }");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/03-estimasi')
                ->assertSee('Paket Customer');
        });
    }

    public function test_05_langganan_aktif_and_estimasi(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        $companyId = $user->company_id;

        $pkg = InternetPackage::factory()->create(['company_id' => $companyId, 'name' => 'Paket Estimasi', 'price' => 200000, 'is_active' => true]);
        $customer = Customer::factory()->create(['company_id' => $companyId, 'is_active' => true]);

        $custInternet = new \App\Models\CustInternet([
            'customer_id' => $customer->id,
            'internet_package_id' => $pkg->id,
            'internet_status' => 'active',
            'account_number' => 'NET-TEST01',
        ]);
        $custInternet->save();

        $custInternet2 = new \App\Models\CustInternet([
            'customer_id' => $customer->id,
            'internet_package_id' => $pkg->id,
            'internet_status' => 'active',
            'account_number' => 'NET-TEST02',
        ]);
        $custInternet2->save();

        $custInternet3 = new \App\Models\CustInternet([
            'customer_id' => $customer->id,
            'internet_package_id' => $pkg->id,
            'internet_status' => 'active',
            'account_number' => 'NET-TEST03',
        ]);
        $custInternet3->save();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/05-langganan-estimasi/01-page')
                ->assertSee('Paket Estimasi')
                ->assertSee('3')
                ->assertSee('600.000');
        });
    }

    public function test_06_delete(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        $pkg = InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Delete Target', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user, $pkg) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            $browser->type('input[placeholder="Cari..."]', 'Delete Target')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10)
                ->screenshot('operator-perusahaan/daftar-paket/06-delete/01-before');

            $browser->click('.fa-trash-alt')
                ->pause(500)
                ->waitForText('Hapus Paket?', 10)
                ->screenshot('operator-perusahaan/daftar-paket/06-delete/02-modal')
                ->press('Hapus')
                ->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/06-delete/03-after')
                ->assertDontSee('Delete Target');
        });
    }

    public function test_07_bulk_delete(): void
    {
        $user = $this->createUserWithPerms(['paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import']);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Bulk Delete 1', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Bulk Delete 2', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[0].click()");
            $browser->script("document.querySelectorAll('tbody input[type=\"checkbox\"]')[1].click()");
            $browser->waitForText('2 data dipilih', 5000)
                ->screenshot('operator-perusahaan/daftar-paket/07-bulk-delete/01-selected')
                ->press('Hapus')
                ->pause(2000)
                ->screenshot('operator-perusahaan/daftar-paket/07-bulk-delete/02-after');
        });
    }

    }
<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\CustInternet;
use App\Models\Customer;
use App\Models\InternetPackage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        InternetPackage::factory()->count(5)->create(['company_id' => $user->company_id, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Paket')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertDontSee('Tgl') // Kolom Tgl sudah dihapus
                ->screenshot('operator-perusahaan/daftar-paket/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
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
        $user = AdminCompany::factory()->create(['is_active' => true]);
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
        $user = AdminCompany::factory()->create(['is_active' => true]);
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
        $user = AdminCompany::factory()->create(['is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'aaa01', 'name' => 'AAA Paket', 'price' => 100000, 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'code' => 'zzz02', 'name' => 'ZZZ Paket', 'price' => 500000, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            // Sort by Kode
            $browser->script("document.querySelectorAll('th[class*=\"cursor-pointer\"]')[0].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/01-code');

            // Sort by Nama
            $browser->script("document.querySelectorAll('th[class*=\"cursor-pointer\"]')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/02-name');

            // Sort by Harga
            $browser->script("document.querySelectorAll('th[class*=\"cursor-pointer\"]')[2].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/03-price');

            // Sort by Langganan Aktif
            $browser->script("document.querySelectorAll('th[class*=\"cursor-pointer\"]')[6].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/daftar-paket/04-sort/04-langganan-aktif')
                ->assertSee('Paket Customer');
        });
    }

    public function test_05_langganan_aktif_and_estimasi(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;

        $pkg = InternetPackage::factory()->create(['company_id' => $companyId, 'name' => 'Paket Estimasi', 'price' => 200000, 'is_active' => true]);
        $customer = Customer::factory()->create(['company_id' => $companyId, 'is_active' => true]);
        CustInternet::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'internet_package_id' => $pkg->id,
            'internet_status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/05-langganan-estimasi/01-page')
                ->assertSee('Paket Estimasi')
                ->assertSee('3') // Langganan Aktif = 3
                ->assertSee('600.000'); // Estimasi = 3 × 200.000
        });
    }

    public function test_06_delete(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
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

            // Click delete button (fa-trash-alt icon)
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
        $user = AdminCompany::factory()->create(['is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Bulk Delete 1', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Bulk Delete 2', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10);

            // Select two checkboxes
            $browser->script("document.querySelectorAll('input[type=\"checkbox\"]')[1].click()");
            $browser->script("document.querySelectorAll('input[type=\"checkbox\"]')[2].click()");
            $browser->pause(1000)
                ->screenshot('operator-perusahaan/daftar-paket/07-bulk-delete/01-selected')
                ->assertSee('data dipilih')
                ->press('Hapus')
                ->pause(2000)
                ->screenshot('operator-perusahaan/daftar-paket/07-bulk-delete/02-after');
        });
    }
}

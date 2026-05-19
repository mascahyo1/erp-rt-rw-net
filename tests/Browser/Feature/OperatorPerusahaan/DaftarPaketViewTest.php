<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->assertSee('Paket Customer')
                ->assertPresent('nav')
                ->assertPresent('table')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertSee('Tambah Paket')
                ->assertSee('Import')
                ->assertSee('Export')
                ->pause(500)
                ->screenshot('operator-perusahaan/daftar-paket/01-page');
        });
    }

    public function test_02_columns_visible(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        \App\Models\InternetPackage::factory()->count(3)->create(['company_id' => $user->company_id, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->assertSee('Nama Paket')
                ->assertSee('Harga')
                ->assertSee('Speed')
                ->assertSee('Quota')
                ->assertSee('Billing')
                ->assertSee('Langganan Aktif')
                ->assertSee('Estimasi Pendapatan')
                ->assertSee('Status')
                ->assertDontSee('Tgl') // Kolom Tgl dihapus
                ->screenshot('operator-perusahaan/daftar-paket/02-columns');
        });
    }
}

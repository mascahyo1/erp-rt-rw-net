<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RiwayatPembayaranViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/riwayat-pembayaran')
                ->waitForText('Riwayat Pembayaran', 10)
                ->pause(1000)
                ->screenshot('pelanggan/riwayat-pembayaran/01-page')
                ->assertPathIs('/customer/riwayat-pembayaran');
        });
    }

    public function test_02_navigate_to_tambah(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/riwayat-pembayaran')
                ->waitForText('Riwayat Pembayaran', 10)
                ->pause(1000)
                ->screenshot('pelanggan/riwayat-pembayaran/02-tambah/01-list')
                ->assertSee('Tambah Pembayaran')
                ->clickLink('Tambah Pembayaran')
                ->pause(1000)
                ->screenshot('pelanggan/riwayat-pembayaran/02-tambah/02-form');
        });
    }

    public function test_03_navigate_to_detail(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/riwayat-pembayaran')
                ->waitForText('Riwayat Pembayaran', 10)
                ->pause(1000)
                ->screenshot('pelanggan/riwayat-pembayaran/03-detail/01-list');

            $browser->click('a[title="Detail"]')
                ->pause(1000)
                ->screenshot('pelanggan/riwayat-pembayaran/03-detail/02-page');
        });
    }
}

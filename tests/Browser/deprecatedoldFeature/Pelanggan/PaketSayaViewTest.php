<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaketSayaViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/paket-saya')
                ->waitForText('Paket Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/01-page')
                ->assertPathIs('/customer/paket-saya');
        });
    }

    public function test_02_navigate_to_tambah(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/paket-saya')
                ->waitForText('Paket Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/02-tambah/01-list')
                ->assertSee('Tambah Paket')
                ->clickLink('Tambah Paket')
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/02-tambah/02-form');
        });
    }

    public function test_03_navigate_to_detail(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/paket-saya')
                ->waitForText('Paket Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/03-detail/01-list')
                ->assertSee('Detail')
                ->clickLink('Detail')
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/03-detail/02-page');
        });
    }
}

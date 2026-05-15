<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TagihanSayaViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/tagihan-saya')
                ->waitForText('Tagihan Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/tagihan-saya/01-page')
                ->assertPathIs('/customer/tagihan-saya');
        });
    }

    public function test_02_list_displays(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/tagihan-saya')
                ->waitForText('Tagihan Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/tagihan-saya/02-list')
                ->assertSee('INV-202505-001')
                ->assertSee('Lunas')
                ->assertSee('Belum Bayar');
        });
    }

    public function test_03_navigate_to_detail(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/tagihan-saya')
                ->waitForText('Tagihan Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/tagihan-saya/03-detail/01-list');

            $browser->click('a[title="Detail"]')
                ->pause(1000)
                ->screenshot('pelanggan/tagihan-saya/03-detail/02-page');
        });
    }
}

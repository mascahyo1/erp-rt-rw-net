<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TagihanDetailViewTest extends DuskTestCase
{
    public function test_01_page_renders_with_data(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/tagihan-saya/detail?id=1')
                ->waitForText('Detail Tagihan', 10)
                ->pause(1000)
                ->screenshot('pelanggan/tagihan-saya/detail/01-page')
                ->assertSee('INV-202505-001')
                ->assertSee('Lunas')
                ->assertSee('Mei 2025');
        });
    }
}

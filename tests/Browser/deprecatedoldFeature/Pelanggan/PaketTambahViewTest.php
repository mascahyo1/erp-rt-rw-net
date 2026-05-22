<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PaketTambahViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/paket-saya/tambah')
                ->waitForText('Tambah Paket', 10)
                ->pause(1000)
                ->screenshot('pelanggan/paket-saya/tambah/01-page')
                ->assertSee('Nama Paket')
                ->assertSee('Kecepatan')
                ->assertSee('Harga')
                ->assertSee('FUP')
                ->assertSee('Simpan')
                ->assertSee('Batal');
        });
    }
}

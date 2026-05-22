<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilSayaViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/profil-saya')
                ->waitForText('Profil Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/profil-saya/01-page')
                ->assertPathIs('/customer/profil-saya');
        });
    }

    public function test_02_edit_form_present(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/profil-saya')
                ->waitForText('Profil Saya', 10)
                ->pause(1000)
                ->screenshot('pelanggan/profil-saya/02-before-edit')
                ->assertSee('Edit Profil')
                ->press('Edit Profil')
                ->pause(1000)
                ->screenshot('pelanggan/profil-saya/03-edit-form')
                ->assertSee('Nama')
                ->assertSee('Email')
                ->assertSee('Simpan')
                ->assertSee('Batal');
        });
    }
}

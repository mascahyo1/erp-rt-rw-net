<?php

namespace Tests\Browser\Feature\Pelanggan;

use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('pelanggan/dashboard/01-page')
                ->assertPathIs('/customer/dashboard');
        });
    }

    public function test_02_stats_displayed(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('pelanggan/dashboard/02-stats')
                ->assertSee('Paket Aktif')
                ->assertSee('Tagihan Bulan Ini')
                ->assertSee('Riwayat Pembayaran');
        });
    }

    public function test_03_navigation(): void
    {
        Customer::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Customer::first(), 'customer')
                ->visit('/customer/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('pelanggan/dashboard/03-nav')
                ->assertSee('Profil Saya')
                ->assertSee('Paket Saya')
                ->assertSee('Tagihan Saya')
                ->assertSee('Riwayat Pembayaran');
        });
    }
}

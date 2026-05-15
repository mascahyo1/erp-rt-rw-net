<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('karyawan/dashboard/01-page')
                ->assertPresent('aside')
                ->assertSee('Dashboard');
        });
    }

    public function test_02_stats_displayed(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('karyawan/dashboard/02-stats')
                ->assertSee('Customer Ditagih')
                ->assertSee('Tagihan Bulan Ini')
                ->assertSee('Insentif Bulan Ini')
                ->assertSee('Pembayaran Collection');
        });
    }

    public function test_03_navigation_links(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('karyawan/dashboard/03-navigation')
                ->assertSee('Dashboard')
                ->assertSee('Profil Saya')
                ->assertSee('Customer')
                ->assertSee('Langganan Customer')
                ->assertSee('Tagihan')
                ->assertSee('Insentif Saya')
                ->assertSee('Riwayat Pembayaran');
        });
    }
}

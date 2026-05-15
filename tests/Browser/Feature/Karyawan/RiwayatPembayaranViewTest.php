<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RiwayatPembayaranViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/riwayat-pembayaran')
                ->waitForText('Riwayat Pembayaran', 10)
                ->pause(1000)
                ->screenshot('karyawan/riwayat-pembayaran/01-page')
                ->assertPresent('table')
                ->assertSee('Riwayat Pembayaran');
        });
    }
}

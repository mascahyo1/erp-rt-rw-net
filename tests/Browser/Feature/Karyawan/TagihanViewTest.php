<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Customer;
use App\Models\CustInternet;
use App\Models\CustInternetInvc;
use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TagihanViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/tagihan')
                ->waitForText('Tagihan', 10)
                ->pause(1000)
                ->screenshot('karyawan/tagihan/01-page')
                ->assertPresent('table')
                ->assertSee('Tagihan');
        });
    }

    public function test_02_list_displays(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        $customer = Customer::factory()->create(['company_id' => $employee->company_id]);

        $custInternet = CustInternet::create([
            'customer_id' => $customer->id,
            'internet_status' => 'active',
            'billing_amount' => 250000,
        ]);

        CustInternetInvc::create([
            'cust_internet_id' => $custInternet->id,
            'invoice_number' => 'INV-202505-001',
            'amount' => 250000,
            'status' => 'Lunas',
            'invoice_due_date' => '2025-05-10',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/tagihan')
                ->waitForText('Tagihan', 10)
                ->pause(1000)
                ->screenshot('karyawan/tagihan/02-list')
                ->assertPresent('table');
        });
    }
}

<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Customer;
use App\Models\CustInternet;
use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LanggananCustomerViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/langganan-customer')
                ->waitForText('Langganan Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/langganan-customer/01-page')
                ->assertPresent('table')
                ->assertSee('Langganan Customer');
        });
    }

    public function test_02_list_displays(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        $customer = Customer::factory()->create(['company_id' => $employee->company_id]);

        CustInternet::create([
            'customer_id' => $customer->id,
            'internet_status' => 'active',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/langganan-customer')
                ->waitForText('Langganan Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/langganan-customer/02-list')
                ->assertPresent('table');
        });
    }
}

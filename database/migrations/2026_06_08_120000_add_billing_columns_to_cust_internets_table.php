<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cust_internets', function (Blueprint $table) {
            // Billing cycle window — used by InvoiceGeneratorService to determine
            // which langganan get invoiced in a given period.
            $table->date('billing_cycle_start')->nullable()->after('internet_status');
            $table->date('billing_cycle_end')->nullable()->after('billing_cycle_start');
            $table->decimal('billing_amount', 15, 2)->nullable()->after('billing_cycle_end');
        });
    }

    public function down(): void
    {
        Schema::table('cust_internets', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle_start', 'billing_cycle_end', 'billing_amount']);
        });
    }
};

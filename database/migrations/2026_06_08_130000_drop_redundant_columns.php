<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // cust_internets: drop billing_cycle_start/end + billing_amount.
        // These are redundant because source of truth is internet_packages:
        //   - price → invoice amount
        //   - billing_cycle → cycle type (D/W/M/Y)
        //   - billing_cycle_start/end in cust_internets was added Day 1
        //     but never used in real logic.
        Schema::table('cust_internets', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle_start', 'billing_cycle_end', 'billing_amount']);
        });

        // cust_internet_invcs: drop legacy 'due_date' (nullable).
        // The official due date column is 'invoice_due_date' (NOT NULL, no default).
        // Keeping both causes confusion and risk of writing to the wrong one.
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('cust_internets', function (Blueprint $table) {
            $table->date('billing_cycle_start')->nullable();
            $table->date('billing_cycle_end')->nullable();
            $table->decimal('billing_amount', 15, 2)->nullable();
        });
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->date('due_date')->nullable();
        });
    }
};

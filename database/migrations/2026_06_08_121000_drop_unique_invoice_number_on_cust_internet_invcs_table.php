<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // invoice_number is per-company series (INV-YYYYMM-NNNN), so global
        // unique constraint blocks multi-tenant bulk generate. Drop the unique
        // index. Absolute identity is still the UUID `id` column.
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->dropUnique('cust_internet_invcs_invoice_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->unique('invoice_number');
        });
    }
};

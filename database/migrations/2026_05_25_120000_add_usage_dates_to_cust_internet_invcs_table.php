<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->date('usage_start_date')->nullable()->after('invoice_number');
            $table->date('usage_end_date')->nullable()->after('usage_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->dropColumn(['usage_start_date', 'usage_end_date']);
        });
    }
};
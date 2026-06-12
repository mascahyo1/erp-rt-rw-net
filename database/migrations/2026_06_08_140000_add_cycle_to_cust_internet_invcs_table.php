<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            // Cycle: daily, weekly, monthly, yearly. Default 'monthly' for
            // backfill (existing 102 invoices assumed monthly from packages).
            $table->enum('cycle', ['daily', 'weekly', 'monthly', 'yearly'])
                ->default('monthly')
                ->after('cust_internet_id');
            $table->index('cycle');
        });
    }

    public function down(): void
    {
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            $table->dropIndex(['cycle']);
            $table->dropColumn('cycle');
        });
    }
};

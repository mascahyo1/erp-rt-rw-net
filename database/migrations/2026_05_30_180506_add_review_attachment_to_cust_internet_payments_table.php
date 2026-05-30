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
        Schema::table('cust_internet_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('cust_internet_payments', 'review_attachment')) {
                $table->string('review_attachment', 500)->nullable()->after('status_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cust_internet_payments', function (Blueprint $table) {
            if (Schema::hasColumn('cust_internet_payments', 'review_attachment')) {
                $table->dropColumn('review_attachment');
            }
        });
    }
};
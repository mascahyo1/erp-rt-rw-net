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
        Schema::table('cust_internets', function (Blueprint $table) {
            $table->string('customer_address', 255)->nullable()->after('router_sn');
            $table->text('customer_address_long')->nullable()->after('customer_address');
            $table->decimal('customer_address_lat', 10, 7)->nullable()->after('customer_address_long');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cust_internets', function (Blueprint $table) {
            $table->dropColumn(['customer_address', 'customer_address_long', 'customer_address_lat']);
        });
    }
};

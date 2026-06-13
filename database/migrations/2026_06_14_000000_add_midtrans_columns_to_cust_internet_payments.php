<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Support\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cust_internet_payments', function (Blueprint $table) {
            $table->string('snap_token', 500)->nullable()->after('data');
            $table->string('midtrans_order_id', 100)->nullable()->unique()->after('snap_token');
            $table->string('midtrans_payment_type', 50)->nullable();
            $table->string('midtrans_va_number', 50)->nullable();
            $table->string('midtrans_fraud_status', 30)->nullable();
            $table->timestamp('midtrans_settled_at')->nullable();
            $table->timestamp('midtrans_expires_at')->nullable();
            $table->index('midtrans_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('cust_internet_payments', function (Blueprint $table) {
            $table->dropIndex(['midtrans_order_id']);
            $table->dropColumn([
                'snap_token',
                'midtrans_order_id',
                'midtrans_payment_type',
                'midtrans_va_number',
                'midtrans_fraud_status',
                'midtrans_settled_at',
                'midtrans_expires_at',
            ]);
        });
    }
};

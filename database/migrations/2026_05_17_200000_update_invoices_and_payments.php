<?php

use App\Support\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom invoice yang lebih lengkap
        Schema::table('cust_internet_invcs', function (Blueprint $table) {
            if (!Schema::hasColumn('cust_internet_invcs', 'total_amount')) {
                $table->decimal('total_amount', 20, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'discount_amount')) {
                $table->decimal('discount_amount', 20, 2)->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'tax_amount')) {
                $table->decimal('tax_amount', 20, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'grand_total')) {
                $table->decimal('grand_total', 20, 2)->nullable()->after('tax_amount');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_due_date');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('cust_internet_invcs', 'description')) {
                $table->text('description')->nullable()->after('status_reason');
            }
        });

        // Tambah kolom payment_date ke cust_internet_payments
        Schema::table('cust_internet_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('cust_internet_payments', 'payment_date')) {
                $table->timestamp('payment_date')->nullable()->after('amount_paid');
            }
        });
    }

    public function down(): void
    {
        // no rollback for safety
    }
};

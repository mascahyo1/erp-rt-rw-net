<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cust_internets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignUuid('internet_package_id')->constrained('internet_packages')->onDelete('cascade');
            $table->string('account_number')->unique();
            $table->string('router_sn')->nullable();
            $table->decimal('usage_upload_kb', 15, 2)->default(0);
            $table->decimal('usage_download_kb', 15, 2)->default(0);
            $table->enum('internet_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('billing_description')->nullable();
            $table->enum('billing_status', ['paid', 'unpaid', 'overdue'])->default('unpaid');
            $table->text('billing_status_description')->nullable();
            $table->date('billing_cycle_start')->nullable();
            $table->date('billing_cycle_end')->nullable();
            $table->decimal('billing_amount', 20, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cust_internets');
    }
};

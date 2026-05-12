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
        Schema::create('cust_internet_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cust_internet_invc_id')->constrained('cust_internet_invcs')->onDelete('restrict');
            $table->decimal('amount_paid', 15, 2);
            $table->enum('status', ['paid', 'pending', 'cancelled', 'rejected', 'expired'])->default('pending');
            $table->string('provider');
            $table->string('payment_method');
            $table->string('proof_file')->nullable();
            $table->json('data')->nullable();
            $table->text('status_description')->nullable();
            $table->text('status_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cust_internet_payments');
    }
};

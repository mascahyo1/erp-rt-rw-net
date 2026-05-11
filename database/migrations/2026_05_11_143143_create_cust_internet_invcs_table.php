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
        Schema::create('cust_internet_invcs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cust_internet_id')->constrained('cust_internets')->onDelete('restrict');
            $table->string('invoice_number')->unique();
            $table->date('invoice_due_date');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['paid', 'unpaid', 'overdue', 'cancelled', 'rejected'])->default('unpaid');
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
        Schema::dropIfExists('cust_internet_invcs');
    }
};

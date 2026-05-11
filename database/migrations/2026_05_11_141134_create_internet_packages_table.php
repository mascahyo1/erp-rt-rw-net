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
        Schema::create('internet_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('restrict');
            $table->string('name');
            $table->decimal('price', 20, 2);
            $table->decimal('speed_down_kbps', 20, 2);
            $table->decimal('speed_up_kbps', 20, 2);
            $table->integer('quota_gb');
            $table->enum('billing_cycle', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly');
            $table->integer('max_devices')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->integer('fup_quota_down')->nullable();
            $table->integer('fup_quota_up')->nullable();
            $table->decimal('fup_speed_down_kbps', 20, 2)->nullable();
            $table->decimal('fup_speed_up_kbps', 20, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internet_packages');
    }
};

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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone_country_code');
            $table->string('phone_number');
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->rememberToken();
            $table->unique(['company_id', 'email'])->index('emp_unique_email');
            $table->unique(['company_id', 'phone_country_code', 'phone_number'])->index('emp_unique_phone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

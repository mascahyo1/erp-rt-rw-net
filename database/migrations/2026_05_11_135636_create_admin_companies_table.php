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
        Schema::create('admin_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('restrict');
            $table->string('name');
            $table->string('email');
            $table->string('phone_country_code');
            $table->string('phone_number');
            $table->boolean('is_active')->default(true);
            $table->string('password');
            $table->rememberToken();
            $table->unique(['company_id', 'email'])->index('admin_comp_unique_email');
            $table->unique(['company_id', 'phone_country_code', 'phone_number'])->index('admin_comp_unique_phone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_companies');
    }
};

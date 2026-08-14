<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_saas', function (Blueprint $table) {
            $table->unique('email', 'admin_saas_unique_email');
        });
    }

    public function down(): void
    {
        Schema::table('admin_saas', function (Blueprint $table) {
            $table->dropUnique('admin_saas_unique_email');
        });
    }
};

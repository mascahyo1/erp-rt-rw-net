<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Support\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_configs', function (Blueprint $table) {
            $table->softDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_configs', function (Blueprint $table) {
            $table->dropSoftDelete();
        });
    }
};

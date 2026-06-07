<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_configs', function (Blueprint $table) {
            $table->renameColumn('descripton', 'description');
        });
    }

    public function down(): void
    {
        Schema::table('saas_configs', function (Blueprint $table) {
            $table->renameColumn('description', 'descripton');
        });
    }
};

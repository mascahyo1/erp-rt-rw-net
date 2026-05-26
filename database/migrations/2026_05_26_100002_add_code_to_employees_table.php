<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'code')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('company_id');
                $table->unique(['company_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'code')) {
                $table->dropUnique(['company_id', 'code']);
                $table->dropColumn('code');
            }
        });
    }
};

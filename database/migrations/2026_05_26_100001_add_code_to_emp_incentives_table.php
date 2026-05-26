<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('emp_incentives', 'code')) {
            Schema::table('emp_incentives', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('name');
                $table->unique(['company_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('emp_incentives', function (Blueprint $table) {
            if (Schema::hasColumn('emp_incentives', 'code')) {
                $table->dropUnique(['company_id', 'code']);
                $table->dropColumn('code');
            }
        });
    }
};

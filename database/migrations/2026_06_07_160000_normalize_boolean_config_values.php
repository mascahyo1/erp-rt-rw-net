<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize existing boolean values to 'true' / 'false' so Vue <select>
        // and PHP filter_var() can work consistently.
        DB::statement("UPDATE saas_configs SET value = 'true' WHERE type = 'boolean' AND value IN ('1', 'true', 'on', 'yes')");
        DB::statement("UPDATE saas_configs SET value = 'false' WHERE type = 'boolean' AND value IN ('0', 'false', 'off', 'no', '')");
        DB::statement("UPDATE company_configs SET value = 'true' WHERE type = 'boolean' AND value IN ('1', 'true', 'on', 'yes')");
        DB::statement("UPDATE company_configs SET value = 'false' WHERE type = 'boolean' AND value IN ('0', 'false', 'off', 'no', '')");
    }

    public function down(): void
    {
        // No-op: rolling forward is the correct direction.
    }
};

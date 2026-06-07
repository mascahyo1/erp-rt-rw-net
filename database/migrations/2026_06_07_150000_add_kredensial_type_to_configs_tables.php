<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE saas_configs MODIFY COLUMN type ENUM('text', 'file', 'number', 'boolean', 'kredensial') NOT NULL");
        DB::statement("ALTER TABLE company_configs MODIFY COLUMN type ENUM('text', 'file', 'number', 'boolean', 'kredensial') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE saas_configs MODIFY COLUMN type ENUM('text', 'file', 'number', 'boolean') NOT NULL");
        DB::statement("ALTER TABLE company_configs MODIFY COLUMN type ENUM('text', 'file', 'number', 'boolean') NOT NULL");
    }
};

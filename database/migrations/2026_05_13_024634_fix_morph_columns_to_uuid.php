<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'companies',
            'admin_companies',
            'roles',
            'employees',
            'internet_packages',
            'customers',
            'cust_internets',
            'cust_internet_invcs',
            'cust_internet_payments',
            'emp_incentives',
            'saas_configs',
            'company_configs',
            'admin_saas',
            'emp_incentive_logs',
        ];

        $idColumns = [
            'created_by_id',
            'updated_by_id',
            'deleted_by_id',
            'restored_by_id',
            'submitted_by_id',
            'reviewed_by_id',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($idColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` CHAR(36) NULL");
                }
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'companies',
            'admin_companies',
            'roles',
            'employees',
            'internet_packages',
            'customers',
            'cust_internets',
            'cust_internet_invcs',
            'cust_internet_payments',
            'emp_incentives',
            'saas_configs',
            'company_configs',
            'admin_saas',
            'emp_incentive_logs',
        ];

        $idColumns = [
            'created_by_id',
            'updated_by_id',
            'deleted_by_id',
            'restored_by_id',
            'submitted_by_id',
            'reviewed_by_id',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($idColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT NULL");
                }
            }
        }
    }
};

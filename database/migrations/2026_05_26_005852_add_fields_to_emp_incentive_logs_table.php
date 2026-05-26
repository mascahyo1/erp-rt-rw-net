<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add columns if they don't exist
        if (!Schema::hasColumn('emp_incentive_logs', 'invoice_number')) {
            Schema::table('emp_incentive_logs', function ($table) {
                $table->string('invoice_number', 50)->nullable()->after('cust_internet_invcs_id');
            });
        }
        if (!Schema::hasColumn('emp_incentive_logs', 'submitted_by_name')) {
            Schema::table('emp_incentive_logs', function ($table) {
                $table->string('submitted_by_name', 255)->nullable()->after('submitted_by_id');
            });
        }
        if (!Schema::hasColumn('emp_incentive_logs', 'reason')) {
            Schema::table('emp_incentive_logs', function ($table) {
                $table->text('reason')->nullable()->after('submitted_by_name');
            });
        }
        if (!Schema::hasColumn('emp_incentive_logs', 'attachment')) {
            Schema::table('emp_incentive_logs', function ($table) {
                $table->string('attachment', 500)->nullable()->after('reason');
            });
        }
        if (!Schema::hasColumn('emp_incentive_logs', 'review_attachment')) {
            Schema::table('emp_incentive_logs', function ($table) {
                $table->string('review_attachment', 500)->nullable()->after('review_reason');
            });
        }
    }

    public function down(): void
    {
        Schema::table('emp_incentive_logs', function ($table) {
            if (Schema::hasColumn('emp_incentive_logs', 'invoice_number')) $table->dropColumn('invoice_number');
            if (Schema::hasColumn('emp_incentive_logs', 'submitted_by_name')) $table->dropColumn('submitted_by_name');
            if (Schema::hasColumn('emp_incentive_logs', 'reason')) $table->dropColumn('reason');
            if (Schema::hasColumn('emp_incentive_logs', 'attachment')) $table->dropColumn('attachment');
            if (Schema::hasColumn('emp_incentive_logs', 'review_attachment')) $table->dropColumn('review_attachment');
        });
    }
};

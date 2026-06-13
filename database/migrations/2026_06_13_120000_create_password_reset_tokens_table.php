<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel token reset password untuk 4 portal.
 *
 * - email: email user (bisa composite key dgn company_id untuk multi-tenant)
 * - company_id: nullable — null untuk admin-saas (single tenant)
 * - guard: salah satu 'admin-saas', 'admin-company', 'employee', 'customer'
 * - token: 64-char random hash dari Laravel Password broker
 *
 * Composite primary key (email, company_id, guard) mencegah collision
 * jika email sama di beberapa company (multi-tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191);
            // company_id: empty string '' untuk admin-saas (single tenant, no company).
            // Tidak nullable karena composite PRIMARY KEY tidak boleh ada NULL member.
            $table->string('company_id', 36)->default('');
            $table->string('guard', 30);
            $table->string('token', 64);
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'company_id', 'guard'], 'password_reset_primary');
            $table->index('company_id', 'password_reset_company_idx');
            $table->index('guard', 'password_reset_guard_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};

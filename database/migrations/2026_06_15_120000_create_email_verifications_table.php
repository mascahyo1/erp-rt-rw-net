<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel token verifikasi email untuk portal Pelanggan.
 *
 * Lifecycle: hanya sekali pakai — setelah customer klik link verifikasi,
 * token di-delete dan `customers.email_verified_at` di-set. Bedanya dengan
 * `password_reset_tokens` (bisa di-reset berkali-kali).
 *
 * Composite primary key (email, company_id) untuk multi-tenant: 1 email bisa
 * dipakai di beberapa company (PT Net Sejahtera, CV Angkasa, dll), jadi token
 * harus unique per-company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->string('email', 191);
            $table->string('company_id', 36);
            $table->string('token', 64);
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'company_id'], 'email_verif_primary');
            $table->index('company_id', 'email_verif_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};

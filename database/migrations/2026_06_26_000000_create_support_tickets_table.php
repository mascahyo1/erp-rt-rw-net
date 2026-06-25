<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel support_tickets (alias: Gangguan) — fitur pelaporan gangguan internet
     * dari customer, dengan flow pengerjaan (open → in_progress → resolved)
     * dan verifikasi oleh admin perusahaan (pending → approved | rejected).
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50);                                              // "TKT-YYYYMMDD-XXXX"
            $table->foreignUuid('cust_internet_id')->constrained('cust_internets')->onDelete('restrict');
            $table->foreignUuid('assigned_to_employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->text('catatan');                                                 // Catatan awal / deskripsi
            $table->enum('status_pengerjaan', ['open', 'in_progress', 'resolved'])->default('open');
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('file_bukti_issue')->nullable();                         // Foto bukti awal
            $table->string('file_bukti_issue_diselesaikan')->nullable();            // Foto bukti resolution
            $table->text('alasan_verifikasi')->nullable();                          // Alasan disetujui / ditolak
            $table->timestamp('issue_dimulai_dari')->useCurrent();                  // Timestamp mulai (sama dengan created_at)
            $table->timestamp('issue_diselesaikan_pada')->nullable();               // Timestamp saat di-resolve
            $table->timestamps();
            $table->blameable();
            $table->softDelete();

            $table->unique(['code']);
            $table->index('status_pengerjaan');
            $table->index('status_verifikasi');
            $table->index('cust_internet_id');
            $table->index('assigned_to_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};

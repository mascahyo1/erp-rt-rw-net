<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table support_ticket_pics — many-to-many antara support_ticket dan employees.
     *
     * 1 tiket bisa punya banyak PIC (karyawan yang handle).
     * Tepat 1 PIC per tiket di-flag sebagai `is_main_pic = true` (PIC utama
     * yang tampil di datatable + judul tiket). PIC tambahan `is_main_pic = false`
     * cuma tampil di detail modal.
     *
     * Legacy `support_tickets.assigned_to_employee_id` tetap dipertahankan
     * (nullable) untuk backward compat — di-backfill otomatis jadi PIC utama
     * kalau tabel pics kosong untuk tiket tsb.
     */
    public function up(): void
    {
        Schema::create('support_ticket_pics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignUuid('employee_id')->constrained('employees')->onDelete('restrict');
            $table->boolean('is_main_pic')->default(false);
            $table->timestamps();
            $table->blameable();
            $table->softDelete();

            // 1 employee gak bisa jadi PIC 2x di tiket yang sama (kecuali soft-deleted)
            $table->unique(['support_ticket_id', 'employee_id', 'deleted_at'], 'uniq_pic_per_ticket');
            $table->index('support_ticket_id');
            $table->index('employee_id');
            $table->index('is_main_pic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_pics');
    }
};

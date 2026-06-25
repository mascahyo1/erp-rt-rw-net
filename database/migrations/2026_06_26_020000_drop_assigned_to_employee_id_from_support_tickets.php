<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy `assigned_to_employee_id` dari support_tickets.
     *
     * SEBELUMNYA: kolom ini dipakai untuk single-PIC (1 tiket = 1 employee).
     * SEKARANG: diganti dengan `support_ticket_pics` pivot table yang
     * support multi-PIC (1 PIC utama + N PIC tambahan) dengan flag `is_main_pic`.
     *
     * Karena fitur baru, gak ada production data yang perlu di-backfill.
     * Legacy support_tickets.assigned_to_employee_id di-drop supaya gak ada
     * dead code / source of confusion di kemudian hari.
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_employee_id']);
            $table->dropColumn('assigned_to_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignUuid('assigned_to_employee_id')->nullable()->constrained('employees')->onDelete('set null');
        });
    }
};

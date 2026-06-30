<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop usage_upload_kb & usage_download_kb dari cust_internets.
     *
     * Kedua kolom ini salah alamat — `cust_internets` adalah tabel
     * langganan (subscription, long-lived), bukan tempat tracking
     * usage bulanan. Tracking usage seharusnya di `cust_internet_invcs`
     * per periode tagihan (yang memang punya usage_start_date /
     * usage_end_date). Untuk sekarang client hanya butuh HR
     * tracking (tiket, insentif) — kolom ini gak dipakai & bikin
     * CRUD error karena NOT NULL tanpa mekanisme populate.
     */
    public function up(): void
    {
        Schema::table('cust_internets', function ($table) {
            $table->dropColumn(['usage_upload_kb', 'usage_download_kb']);
        });
    }

    public function down(): void
    {
        Schema::table('cust_internets', function ($table) {
            $table->decimal('usage_upload_kb', 15, 2)->default(0);
            $table->decimal('usage_download_kb', 15, 2)->default(0);
        });
    }
};

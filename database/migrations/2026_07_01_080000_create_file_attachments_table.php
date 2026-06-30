<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel polymorphic `file_attachments` — generic multi-file attachment.
     *
     * Relasi: `attachable` (model_type + model_id). Dipakai oleh:
     *   - Gangguan (`type` = bukti_issue | bukti_issue_selesai)
     *   - Future models (Tagihan, Pembayaran, Karyawan, dll — tinggal tambah
     *     type value + Enum case).
     *
     * Kolom:
     *   - file_name: label/keterampilan user-facing (mis. "Foto router mati")
     *   - file_path: path MinIO (system-generated, NEVER user input)
     *   - file_description: catatan bebas dari user
     */
    public function up(): void
    {
        Schema::create('file_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('attachable');                                          // model_type + model_id
            $table->string('type', 64);                                                // FK konseptual ke FileAttachmentType
            $table->string('file_name', 255);                                          // User-facing label
            $table->string('file_path', 512);                                          // Path di MinIO (system)
            $table->text('file_description')->nullable();                               // Catatan user (nullable)
            $table->timestamps();
            $table->blameable();
            $table->softDelete();

            $table->index('type');
            $table->index(['attachable_type', 'attachable_id', 'type'], 'idx_attachable_type_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_attachments');
    }
};

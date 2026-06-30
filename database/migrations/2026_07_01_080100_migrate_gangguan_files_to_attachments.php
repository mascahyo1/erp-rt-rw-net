<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Support\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\FileAttachmentType;
use Illuminate\Support\Str;

/**
 * Migrate `support_tickets.file_bukti_issue` & `file_bukti_issue_diselesaikan`
 * ke tabel polymorphic `file_attachments`. Tiap row existing jadi 1 row di
 * `file_attachments`:
 *   - type = BuktiIssue | BuktiIssueSelesai
 *   - file_path = path existing (TIDAK di-copy — file sudah ada di MinIO)
 *   - file_name = basename(path) + uuid short suffix kalau duplicate
 *   - file_description = null (legacy rows gak punya caption)
 *
 * Setelah migrate sukses, drop kolom lama dari `support_tickets`.
 *
 * Note: idempotency (down() aman restore kolom, up() aman re-run dengan
 * guard `WHERE NOT EXISTS`).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate file_bukti_issue
        DB::table('support_tickets')
            ->whereNotNull('file_bukti_issue')
            ->where('file_bukti_issue', '!=', '')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $exists = DB::table('file_attachments')
                        ->where('attachable_type', \App\Models\Gangguan::class)
                        ->where('attachable_id', $row->id)
                        ->where('type', FileAttachmentType::BuktiIssue->value)
                        ->where('file_path', $row->file_bukti_issue)
                        ->exists();
                    if ($exists) continue;

                    $fileName = $this->deriveFileName($row->file_bukti_issue);
                    DB::table('file_attachments')->insert([
                        'id' => (string) Str::uuid(),
                        'attachable_type' => \App\Models\Gangguan::class,
                        'attachable_id' => $row->id,
                        'type' => FileAttachmentType::BuktiIssue->value,
                        'file_name' => $fileName,
                        'file_path' => $row->file_bukti_issue,
                        'file_description' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        // 2. Migrate file_bukti_issue_diselesaikan
        DB::table('support_tickets')
            ->whereNotNull('file_bukti_issue_diselesaikan')
            ->where('file_bukti_issue_diselesaikan', '!=', '')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $exists = DB::table('file_attachments')
                        ->where('attachable_type', \App\Models\Gangguan::class)
                        ->where('attachable_id', $row->id)
                        ->where('type', FileAttachmentType::BuktiIssueSelesai->value)
                        ->where('file_path', $row->file_bukti_issue_diselesaikan)
                        ->exists();
                    if ($exists) continue;

                    $fileName = $this->deriveFileName($row->file_bukti_issue_diselesaikan);
                    DB::table('file_attachments')->insert([
                        'id' => (string) Str::uuid(),
                        'attachable_type' => \App\Models\Gangguan::class,
                        'attachable_id' => $row->id,
                        'type' => FileAttachmentType::BuktiIssueSelesai->value,
                        'file_name' => $fileName,
                        'file_path' => $row->file_bukti_issue_diselesaikan,
                        'file_description' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        // 3. Drop old columns dari support_tickets
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['file_bukti_issue', 'file_bukti_issue_diselesaikan']);
        });
    }

    public function down(): void
    {
        // Restore column types (string nullable, sama kayak original).
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('file_bukti_issue')->nullable()->after('status_verifikasi');
            $table->string('file_bukti_issue_diselesaikan')->nullable()->after('file_bukti_issue');
        });

        // Best-effort restore: ambil file_attachments paling lama per (attachable_id, type)
        // dan copy balik ke kolom legacy. Data hilang caption/file_name di-copy.
        DB::table('file_attachments')
            ->where('attachable_type', \App\Models\Gangguan::class)
            ->where('type', FileAttachmentType::BuktiIssue->value)
            ->orderBy('created_at')
            ->chunk(200, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('support_tickets')
                        ->where('id', $r->attachable_id)
                        ->update(['file_bukti_issue' => $r->file_path]);
                }
            });
        DB::table('file_attachments')
            ->where('attachable_type', \App\Models\Gangguan::class)
            ->where('type', FileAttachmentType::BuktiIssueSelesai->value)
            ->orderBy('created_at')
            ->chunk(200, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('support_tickets')
                        ->where('id', $r->attachable_id)
                        ->update(['file_bukti_issue_diselesaikan' => $r->file_path]);
                }
            });
    }

    /**
     * Derive user-facing file_name dari path MinIO legacy.
     * Mis. `gangguan/issues/abc.jpg` → `abc.jpg`.
     */
    private function deriveFileName(string $path): string
    {
        $parts = explode('/', $path);
        return end($parts) ?: $path;
    }
};

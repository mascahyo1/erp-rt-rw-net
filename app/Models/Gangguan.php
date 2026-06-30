<?php

namespace App\Models;

use App\Enums\FileAttachmentType;
use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasFileAttachments;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gangguan (alias: SupportTicket) — tiket pelaporan gangguan internet.
 *
 * Flow:
 *   1. Customer / Karyawan / Perusahaan create tiket → status_pengerjaan=open, status_verifikasi=pending
 *   2. Karyawan / Perusahaan kerja → status_pengerjaan=in_progress + set assigned_to_employee_id
 *   3. Selesaikan → status_pengerjaan=resolved + upload attachments (bukti_issue_selesai) + set issue_diselesaikan_pada
 *   4. Admin Perusahaan verify → status_verifikasi=approved|rejected + alasan_verifikasi
 *
 * Multi-file attachment via polymorphic `file_attachments` table
 * (lihat {@see HasFileAttachments}).
 */
class Gangguan extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete, HasFileAttachments;

    protected $table = 'support_tickets';

    protected $fillable = [
        'id',
        'code',
        'cust_internet_id',
        'assigned_to_employee_id',
        'catatan',
        'status_pengerjaan',
        'status_verifikasi',
        'alasan_verifikasi',
        'issue_dimulai_dari',
        'issue_diselesaikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'status_pengerjaan' => SupportTicketPengerjaanStatus::class,
            'status_verifikasi' => SupportTicketVerifikasiStatus::class,
            'issue_dimulai_dari' => 'datetime',
            'issue_diselesaikan_pada' => 'datetime',
        ];
    }

    public function custInternet(): BelongsTo
    {
        return $this->belongsTo(CustInternet::class);
    }

    /**
     * Semua PIC (main + tambahan). TIDAK include soft-deleted.
     * Di-load eager di controller `index()` untuk hindari N+1.
     */
    public function pics(): HasMany
    {
        return $this->hasMany(SupportTicketPic::class, 'support_ticket_id');
    }

    /**
     * PIC utama (is_main_pic = true). Return null kalau tidak ada.
     */
    public function getMainPicAttribute(): ?SupportTicketPic
    {
        return $this->pics->where('is_main_pic', true)->first();
    }

    public function getMainPicNameAttribute(): ?string
    {
        return $this->main_pic?->employee?->name;
    }

    public function getAdditionalPicsAttribute()
    {
        return $this->pics->where('is_main_pic', false)->values();
    }

    /**
     * Backward-compat legacy URL getter.
     * DEPRECATED: pakai $gangguan->attachmentsByType(FileAttachmentType::BuktiIssue)->first()?->url
     */
    public function getFileBuktiIssueUrlAttribute(): ?string
    {
        $first = $this->attachmentsByType(FileAttachmentType::BuktiIssue)->first();
        return $first?->url;
    }

    public function getFileBuktiIssueDiselesaikanUrlAttribute(): ?string
    {
        $first = $this->attachmentsByType(FileAttachmentType::BuktiIssueSelesai)->first();
        return $first?->url;
    }

    /**
     * Backfill PIC: kalau tiket punya assigned_to_employee_id tapi belum ada PIC record,
     * otomatis create PIC utama (idempotent: skip kalau sudah ada).
     */
    public function backfillMainPicIfNeeded(): void
    {
        // Placeholder: kalau di masa depan ada logic backfill, taruh di sini.
        // Untuk sekarang, PIC hanya dibuat dari form (bukan auto-backfill).
    }
}

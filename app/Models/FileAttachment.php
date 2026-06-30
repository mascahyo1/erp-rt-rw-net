<?php

namespace App\Models;

use App\Enums\FileAttachmentType;
use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * FileAttachment — polymorphic single-table untuk SEMUA file upload di app.
 *
 * Satu row per file. Attach ke model apapun via `attachable_type` + `attachable_id`.
 * Tiap attachment punya `type` (FK konseptual ke {@see FileAttachmentType}).
 *
 * URL akses: pakai route `file.proxy` dengan disk='minio'. Lazy accessor di
 * {@see getUrlAttribute} — gak di-cache (file di MinIO bisa di-rotate).
 */
class FileAttachment extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'file_attachments';

    protected $fillable = [
        'id',
        'attachable_type',
        'attachable_id',
        'type',
        'file_name',
        'file_path',
        'file_description',
    ];

    protected function casts(): array
    {
        return [
            'type' => FileAttachmentType::class,
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * URL proxied via MinIO → browser (signed, gak public).
     */
    public function getUrlAttribute(): string
    {
        return route('file.proxy', [
            'path' => $this->file_path,
            'disk' => 'minio',
        ]);
    }

    public function getFileNameWithExtensionAttribute(): string
    {
        $ext = pathinfo($this->file_path, PATHINFO_EXTENSION);
        return $ext ? "{$this->file_name}.{$ext}" : $this->file_name;
    }

    /**
     * Hapus file dari MinIO + soft delete row.
     */
    public function deleteFromStorage(): bool
    {
        try {
            if ($this->file_path && Storage::disk('minio')->exists($this->file_path)) {
                Storage::disk('minio')->delete($this->file_path);
            }
        } catch (\Throwable $e) {
            // Storage error gak boleh block soft delete — biar gak orphan record
            \Log::warning("FileAttachment deleteFromStorage gagal: {$this->id}", ['err' => $e->getMessage()]);
        }
        return (bool) $this->delete();
    }
}

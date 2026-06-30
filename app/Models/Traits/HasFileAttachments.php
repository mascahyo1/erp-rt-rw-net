<?php

namespace App\Models\Traits;

use App\Enums\FileAttachmentType;
use App\Models\FileAttachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Taruh di model apapun yang butuh polymorphic attachments.
 *
 * Usage:
 *   class Gangguan extends Model {
 *       use HasFileAttachments;
 *   }
 *
 * Lalu:
 *   $gangguan->attachments; // morphMany FileAttachment semua type
 *   $gangguan->attachmentsByType(FileAttachmentType::BuktiIssue); // filtered
 *   $gangguan->attachFile(
 *       file: $request->file('lampiran'),
 *       type: FileAttachmentType::BuktiIssue,
 *       fileName: 'Foto router mati',
 *       fileDescription: 'Ini terjadi kemarin',
 *       folder: 'gangguan/issues'
 *   );
 *   $gangguan->syncAttachments(
 *       uploads: [...],  // array of {file, type, file_name, file_description}
 *       keepExistingIds: [...] // attachment IDs to keep (lainnya dihapus)
 *   );
 *
 * Folder default di MinIO: `attachments/{model_slug}/{id}/{type}`.
 */
trait HasFileAttachments
{
    /**
     * MorphMany: semua attachment attach ke model ini.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(FileAttachment::class, 'attachable')
            ->whereNull('deleted_at');
    }

    /**
     * Attachment ter-filter by type (eager-load friendly).
     *
     * @return MorphMany
     */
    public function attachmentsByType(FileAttachmentType $type, string $direction = 'asc'): MorphMany
    {
        return $this->attachments()->where('type', $type->value)->orderBy('created_at', $direction);
    }

    /**
     * Attach satu file ke model ini.
     *
     * @param UploadedFile $file
     * @param FileAttachmentType $type
     * @param string|null $fileName User-facing label. Default = original client name.
     * @param string|null $fileDescription User note (nullable).
     * @param string|null $folder Override folder di MinIO. Default: attachments/{model_slug}/{type}
     * @return FileAttachment
     */
    public function attachFile(
        UploadedFile $file,
        FileAttachmentType $type,
        ?string $fileName = null,
        ?string $fileDescription = null,
        ?string $folder = null,
    ): FileAttachment {
        $folder ??= $this->defaultAttachmentsFolder() . '/' . $type->value;
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $uniqueName = uniqid('', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $file->storeAs($folder, $uniqueName, ['disk' => 'minio', 'visibility' => 'private']);

        return $this->attachments()->create([
            'type' => $type->value,
            'file_name' => $fileName ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
            'file_description' => $fileDescription,
        ]);
    }

    /**
     * Sync attachments: upload baru + hapus yg gak ada di keepExistingIds.
     *
     * @param array $uploads Setiap item: {file: UploadedFile, type: FileAttachmentType, file_name?: string, file_description?: string|null}
     * @param array $keepExistingIds ID FileAttachment yg mau dipertahankan. LAINNYA = soft delete + hapus dari MinIO.
     * @return array Newly created attachments
     */
    public function syncAttachments(array $uploads, array $keepExistingIds = []): array
    {
        // 1. Hapus attachment existing yang gak di-keep
        $existing = $this->attachments()->get();
        foreach ($existing as $att) {
            if (!in_array($att->id, $keepExistingIds, true)) {
                $att->deleteFromStorage();
            }
        }

        // 2. Create new uploads
        $created = [];
        foreach ($uploads as $upload) {
            $created[] = $this->attachFile(
                file: $upload['file'],
                type: $upload['type'],
                fileName: $upload['file_name'] ?? null,
                fileDescription: $upload['file_description'] ?? null,
                folder: $upload['folder'] ?? null,
            );
        }

        return $created;
    }

    /**
     * Folder default untuk attachments model ini (overridable di child).
     * Default pattern: attachments/{snake_case_class_basename}
     */
    protected function defaultAttachmentsFolder(): string
    {
        $base = class_basename($this);
        return 'attachments/' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base));
    }
}

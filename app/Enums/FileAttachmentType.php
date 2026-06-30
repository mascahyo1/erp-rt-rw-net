<?php

namespace App\Enums;

/**
 * Tipe-tipe attachment yang dipakai `file_attachments.type` (polymorphic).
 *
 * Saat ini:
 *   - BuktiIssue: bukti awal gangguan (created by customer/karyawan/perusahaan)
 *   - BuktiIssueSelesai: bukti penyelesaian (karyawan/perusahaan)
 *
 * Future: tambah case baru (mis. TagihanAttachment, PembayaranBukti, dll).
 * Jangan hapus case existing — boleh rename dengan deprecated() annotation,
 * tapi jangan drop (karena historical rows reference value tsb).
 */
enum FileAttachmentType: string
{
    case BuktiIssue = 'bukti_issue';
    case BuktiIssueSelesai = 'bukti_issue_selesai';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::BuktiIssue => 'Bukti Awal Gangguan',
            self::BuktiIssueSelesai => 'Bukti Penyelesaian',
        };
    }
}

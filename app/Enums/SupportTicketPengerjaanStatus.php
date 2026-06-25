<?php

namespace App\Enums;

/**
 * Status pengerjaan tiket (workflow internal karyawan/perusahaan).
 *
 * Flow:
 *   open         → tiket baru dibuat (belum ada yang kerja)
 *   in_progress  → sudah ada karyawan yg kerja (assigned_to_employee_id di-set)
 *   resolved     → sudah selesai, file_bukti_issue_diselesaikan di-upload
 *                  (siap diverifikasi oleh admin perusahaan)
 */
enum SupportTicketPengerjaanStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Belum Dikerjakan',
            self::IN_PROGRESS => 'Sedang Dikerjakan',
            self::RESOLVED => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'amber',
            self::IN_PROGRESS => 'sky',
            self::RESOLVED => 'emerald',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

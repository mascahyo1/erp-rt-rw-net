<?php

namespace App\Enums;

/**
 * Status verifikasi tiket (gate approval oleh admin perusahaan).
 *
 * Flow:
 *   pending  → baru dibuat atau baru di-resolve, menunggu verifikasi admin
 *   approved → admin perusahaan setuju hasil resolution
 *   rejected → admin perusahaan tolak hasil resolution (tiket perlu kerja ulang)
 */
enum SupportTicketVerifikasiStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::APPROVED => 'emerald',
            self::REJECTED => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

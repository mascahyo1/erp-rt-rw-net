<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete, HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone_country_code',
        'phone_number',
        'is_active',
        'address',
        'description',
        'logo',
        'logo_dark',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    /**
     * Get logo URL (light variant) for proxy via file.proxy route.
     * Dipakai di tagihan/riwayat pembayaran list (web view).
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? route('file.proxy', ['path' => $this->logo, 'disk' => 'minio'], false) : null;
    }

    /**
     * Get dark-mode logo URL for proxy via file.proxy route.
     */
    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->logo_dark ? route('file.proxy', ['path' => $this->logo_dark, 'disk' => 'minio'], false) : null;
    }

    /**
     * Get the company logo as a base64 data URI (data:image/...;base64,...).
     *
     * Use untuk server-side PDF/Word generation (DomPDF, PhpWord) yang
     * TIDAK bisa authenticate ke file.proxy route (server-side fetch dapat
     * 302 redirect ke login). Embedding base64 = no HTTP request, reliable.
     *
     * @param  string  $variant  'logo' (light) atau 'logo_dark'
     * @param  string  $disk     Storage disk (default: 'minio')
     * @return string|null       data URI, atau null kalau no logo / file missing
     */
    public function getLogoDataUriAttribute(?string $disk = 'minio'): ?string
    {
        return $this->getLogoDataUri('logo', $disk);
    }

    public function getLogoDataUri(string $variant = 'logo', string $disk = 'minio'): ?string
    {
        $path = $this->{$variant};
        if (!$path) {
            return null;
        }

        try {
            if (!Storage::disk($disk)->exists($path)) {
                return null;
            }
            $contents = Storage::disk($disk)->get($path);
            $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (\Throwable $e) {
            return null;
        }
    }
}


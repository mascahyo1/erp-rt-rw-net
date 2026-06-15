<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasUuidV7, HasBlameable, HasSoftDelete, Notifiable, HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        'name',
        'email',
        'email_verified_at',
        'phone_country_code',
        'phone_number',
        'no_nik',
        'photo_ktp',
        'no_kk',
        'photo_kk',
        'photo_profile',
        'address',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * TRUE kalau customer sudah pernah klik link verifikasi email.
     * NULL email_verified_at = belum verified → di-block dari login.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Composite key untuk lookup token di password_reset_tokens.
     * Multi-tenant — email SAMA di company BERBEDA harus jadi token BERBEDA.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email . '||' . ($this->company_id ?? '');
    }

    /**
     * Composite key untuk lookup token di email_verifications.
     * Multi-tenant — 1 customer bisa ada di beberapa company (saat ini 1, tapi
     * schema siap untuk multi-company), jadi key harus include company_id.
     */
    public function getEmailForVerification(): string
    {
        return $this->email . '||' . ($this->company_id ?? '');
    }

    /**
     * Override default Laravel ResetPassword notification dengan custom branded
     * notification. Multi-tenant — company_id di-pass ke URL agar token scoped
     * per company.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token, 'customer', $this->company_id));
    }

    /**
     * Kirim email verifikasi via custom branded notification.
     * company_id di-pass agar URL verifikasi scoped per company.
     *
     * Method ini TIDAK override parent (yang signature-nya no-arg). Customer
     * tidak implement MustVerifyEmail contract — email verification manual
     * via controller flow dengan parameter $token.
     */
    public function sendCustomEmailVerificationNotification($token): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification($token, $this->company_id));
    }
}

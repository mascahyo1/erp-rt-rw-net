<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasPermission;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminCompany extends Authenticatable
{
    use HasUuidV7, HasBlameable, HasSoftDelete, HasFactory, HasPermission, Notifiable;

    protected $table = 'admin_companies';

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'email',
        'phone_country_code',
        'phone_number',
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
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Composite key untuk lookup token di password_reset_tokens.
     * Multi-tenant — email SAMA di company BERBEDA harus jadi token BERBEDA.
     * Format: "{email}||{company_id}" (pakai "||" sebagai separator agar tidak conflict dgn format email).
     */
    public function getEmailForPasswordReset(): string
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
        $this->notify(new \App\Notifications\ResetPasswordNotification($token, 'admin-company', $this->company_id));
    }
}

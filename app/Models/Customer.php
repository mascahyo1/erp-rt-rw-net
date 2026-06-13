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
     * Override default Laravel ResetPassword notification dengan custom branded
     * notification. Multi-tenant — company_id di-pass ke URL agar token scoped
     * per company.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token, 'customer', $this->company_id));
    }
}

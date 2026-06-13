<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasPermission;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminSaas extends Authenticatable
{
    use HasFactory, Notifiable, HasUuidV7, HasBlameable, HasSoftDelete, HasPermission;

    protected $table = 'admin_saas';

    protected $fillable = [
        'id',
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
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    /**
     * Override default Laravel ResetPassword notification dengan custom branded
     * notification. Single-tenant (admin-saas tidak punya company_id).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token, 'admin-saas', null));
    }
}

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
        'name',
        'email',
        'phone_country_code',
        'phone_number',
        'no_nik',
        'photo_nik',
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
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

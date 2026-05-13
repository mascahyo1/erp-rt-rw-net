<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'employees';

    protected $fillable = [
        'id',
        'company_id',
        'no_nik',
        'photo_nik',
        'no_kk',
        'photo_kk',
        'photo_profile',
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
}

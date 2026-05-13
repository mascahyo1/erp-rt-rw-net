<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

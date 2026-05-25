<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

class SaasConfig extends Model
{
    use HasUuidV7, HasBlameable;

    protected $table = 'saas_configs';

    protected $fillable = [
        'id',
        'key',
        'type',
        'value',
        'descripton',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    public function createdBy()
    {
        return $this->morphTo('created_by');
    }

    public function updatedBy()
    {
        return $this->morphTo('updated_by');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $config = static::where('key', $key)->first();
        return $config?->value ?? $default;
    }
}

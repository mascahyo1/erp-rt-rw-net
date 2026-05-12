<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait HasUuidV7
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public static function bootHasUuidV7(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid7();
            }
        });
    }
}

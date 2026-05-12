<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasBlameable
{
    public static function bootHasBlameable(): void
    {
        static::creating(function ($model) {
            if ($user = auth()->user()) {
                $model->created_by_type = $user->getMorphClass();
                $model->created_by_id   = $user->getKey();
            }
        });

        static::updating(function ($model) {
            if ($user = auth()->user()) {
                $model->updated_by_type = $user->getMorphClass();
                $model->updated_by_id   = $user->getKey();
            }
        });
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }
}

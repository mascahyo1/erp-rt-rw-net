<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasRestored
{
    use SoftDeletes;

    public static function bootHasRestored(): void
    {
        static::restoring(function ($model) {
            $model->deleted_by_type = null;
            $model->deleted_by_id = null;
            $model->restored_at = now();

            if ($user = auth()->user()) {
                $model->restored_by_type = $user->getMorphClass();
                $model->restored_by_id = $user->getKey();
            }
        });

        static::restored(function ($model) {
            if (method_exists($model, 'onRestored')) {
                $model->onRestored();
            }
        });
    }

    public function getDeletedByColumn(): string
    {
        return 'deleted_by_type';
    }

    public function getRestoredAtColumn(): string
    {
        return 'restored_at';
    }

    public function getRestoredByTypeColumn(): string
    {
        return 'restored_by_type';
    }

    public function getRestoredByIdColumn(): string
    {
        return 'restored_by_id';
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function deletedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function restoredBy(): MorphTo
    {
        return $this->morphTo();
    }
}

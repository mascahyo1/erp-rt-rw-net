<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasSoftDelete
{
    use SoftDeletes;

    public static function bootHasSoftDelete(): void
    {
        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                return;
            }

            if ($user = auth()->user()) {
                $model->deleted_by_type = $user->getMorphClass();
                $model->deleted_by_id   = $user->getKey();
            }
        });

        static::restoring(function ($model) {
            $model->deleted_by_type = null;
            $model->deleted_by_id   = null;
            $model->restored_at     = now();

            if ($user = auth()->user()) {
                $model->restored_by_type = $user->getMorphClass();
                $model->restored_by_id   = $user->getKey();
            }
        });

        static::restored(function ($model) {
            if (method_exists($model, 'onRestored')) {
                $model->onRestored();
            }
        });
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

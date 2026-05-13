<?php

namespace App\Models;

use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelHasRole extends Model
{
    use HasUuidV7;

    protected $table = 'model_has_roles';

    protected $fillable = [
        'id', 'role_id', 'model_id', 'model_type',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function model()
    {
        return $this->morphTo();
    }
}

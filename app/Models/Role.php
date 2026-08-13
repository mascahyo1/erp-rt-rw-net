<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Role extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'roles';

    protected $fillable = [
        'id', 'scope', 'company_id', 'name', 'display_order',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'display_order' => 'integer', 'deleted_at' => 'datetime', 'restored_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->using(RolePermission::class)
            ->withTimestamps();
    }

    public function modelHasRoles(): HasMany
    {
        return $this->hasMany(ModelHasRole::class);
    }

    public function createdBy(): MorphTo { return $this->morphTo('created_by'); }
    public function updatedBy(): MorphTo { return $this->morphTo('updated_by'); }
}

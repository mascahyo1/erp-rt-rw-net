<?php

namespace App\Models;

use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuidV7;

    protected $table = 'permissions';

    protected $fillable = [
        'id', 'name', 'scope', 'display_order', 'description',
    ];

    protected function casts(): array
    {
        return ['display_order' => 'integer'];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}

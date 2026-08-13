<?php

namespace App\Models;

use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
    use HasUuidV7;

    protected $table = 'role_permissions';

    public $timestamps = true;

    protected $fillable = ['id', 'role_id', 'permission_id'];
}

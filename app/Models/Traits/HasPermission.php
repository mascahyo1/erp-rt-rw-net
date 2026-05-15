<?php

namespace App\Models\Traits;

trait HasPermission
{
    public function roles()
    {
        return $this->morphToMany(\App\Models\Role::class, 'model', 'model_has_roles', 'model_id', 'role_id')
            ->withTimestamps();
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->canPermission($permissionName);
    }

    public function canPermission(string $permissionName): bool
    {
        static $cache = [];

        $key = $this->getMorphClass() . ':' . $this->getKey();

        if (! isset($cache[$key])) {
            $cache[$key] = $this->roles()
                ->where('is_active', true)
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->toArray();
        }

        return in_array($permissionName, $cache[$key] ?? []);
    }

    public function getAllPermissionNames(): array
    {
        return $this->roles()
            ->where('is_active', true)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }
}

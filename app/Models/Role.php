<?php

namespace App\Models;

use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['tenant_id', 'name', 'slug', 'description', 'permissions', 'is_system', 'is_active'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public static function ensureDefaultsForTenant(?int $tenantId): void
    {
        if (! $tenantId) {
            return;
        }

        foreach (PermissionCatalog::defaultRoles() as $role) {
            self::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'permissions' => PermissionCatalog::defaultRolePermissions($role['slug']),
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        User::where('tenant_id', $tenantId)
            ->whereNull('role_id')
            ->get()
            ->each(function (User $user) use ($tenantId): void {
                $role = self::where('tenant_id', $tenantId)
                    ->where('slug', $user->role ?: 'viewer')
                    ->first()
                    ?: self::where('tenant_id', $tenantId)->where('slug', 'viewer')->first();

                if ($role) {
                    $user->forceFill(['role_id' => $role->id])->save();
                }
            });
    }

    public static function uniqueSlug(?int $tenantId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $counter = 2;

        while (self::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
